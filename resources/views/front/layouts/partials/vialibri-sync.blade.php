<?php echo '<?xml version="1.0" encoding="UTF-8" ?>'; ?>
<Sync_Data>
    <update>
        <date_update>{{ $feed['date_update'] }}</date_update>
    </update>
    <ID_set>
        @foreach ($feed['ids'] as $id)
            <id>{{ $id }}</id>
        @endforeach
    </ID_set>
</Sync_Data>
