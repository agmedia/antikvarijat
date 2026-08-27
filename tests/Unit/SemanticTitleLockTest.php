<?php

namespace Tests\Unit;

use App\Models\Back\Catalog\Concerns\FindsSemanticallyEquivalentTitles;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SemanticTitleLockTest extends TestCase
{
    public function test_mysql_lock_uses_the_write_pdo_and_releases_in_finally(): void
    {
        $connection = Mockery::mock(Connection::class);
        $lockName = null;

        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldReceive('selectOne')
            ->once()
            ->ordered()
            ->withArgs(function ($query, $bindings, $useReadPdo) use (&$lockName): bool {
                $lockName = $bindings[0] ?? null;

                return $query === 'SELECT GET_LOCK(?, ?) AS acquired'
                    && ($bindings[1] ?? null) === 10
                    && $useReadPdo === false
                    && is_string($lockName)
                    && strlen($lockName) === 62;
            })
            ->andReturn((object) ['acquired' => 1]);
        $connection->shouldReceive('selectOne')
            ->once()
            ->ordered()
            ->withArgs(function ($query, $bindings, $useReadPdo) use (&$lockName): bool {
                return $query === 'SELECT RELEASE_LOCK(?) AS released'
                    && ($bindings[0] ?? null) === $lockName
                    && $useReadPdo === false;
            })
            ->andReturn((object) ['released' => 1]);

        SemanticTitleLockModel::$connectionStub = $connection;

        try {
            SemanticTitleLockModel::withSemanticTitleLock('Testni autor', function (): void {
                throw new RuntimeException('callback failed');
            });
            $this->fail('The callback exception was not propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('callback failed', $exception->getMessage());
        } finally {
            SemanticTitleLockModel::$connectionStub = null;
        }
    }

    public function test_mysql_lock_timeout_does_not_run_the_callback(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldReceive('selectOne')
            ->once()
            ->with('SELECT GET_LOCK(?, ?) AS acquired', Mockery::on(function ($bindings): bool {
                return count($bindings) === 2 && $bindings[1] === 10;
            }), false)
            ->andReturn((object) ['acquired' => 0]);

        SemanticTitleLockModel::$connectionStub = $connection;
        $called = false;

        try {
            SemanticTitleLockModel::withSemanticTitleLock('Zauzeti autor', function () use (&$called): void {
                $called = true;
            });
            $this->fail('The lock timeout was not raised.');
        } catch (LockTimeoutException $exception) {
            $this->assertFalse($called);
        } finally {
            SemanticTitleLockModel::$connectionStub = null;
        }
    }
}

class SemanticTitleLockModel extends Model
{
    use FindsSemanticallyEquivalentTitles;

    public static $connectionStub;

    protected $table = 'authors';

    public function getConnection()
    {
        return static::$connectionStub;
    }
}
