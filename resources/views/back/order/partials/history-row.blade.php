<tr>
    <td class="font-size-base" data-label="Vrsta">
        @if ($record->status)
            <span class="badge badge-pill badge-{{ $record->status->color }}">{{ $record->status->title }}</span>
        @else
            <small>Komentar</small>
        @endif
    </td>
    <td data-label="Vrijeme">
        <span class="font-w600 admin-history-relative">{{ \Illuminate\Support\Carbon::make($record->created_at)->locale('hr_HR')->diffForHumans() }}</span>
        <span class="font-weight-light admin-history-date">{{ \Illuminate\Support\Carbon::make($record->created_at)->format('d.m.Y. H:i') }}</span>
    </td>
    <td data-label="Korisnik">
        <a href="javascript:void(0)">{{ $record->user ? $record->user->name : $order->shipping_fname . ' ' . $order->shipping_lname }}</a>
    </td>
    <td data-label="Komentar">{{ $record->comment }}</td>
</tr>
