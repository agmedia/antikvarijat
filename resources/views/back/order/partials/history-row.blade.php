<tr>
    <td class="font-size-base">
        @if ($record->status)
            <span class="badge badge-pill badge-{{ $record->status->color }}">{{ $record->status->title }}</span>
        @else
            <small>Komentar</small>
        @endif
    </td>
    <td>
        <span class="font-w600">{{ \Illuminate\Support\Carbon::make($record->created_at)->locale('hr_HR')->diffForHumans() }}</span> /
        <span class="font-weight-light">{{ \Illuminate\Support\Carbon::make($record->created_at)->format('d.m.Y - h:i') }}</span>
    </td>
    <td>
        <a href="javascript:void(0)">{{ $record->user ? $record->user->name : $order->shipping_fname . ' ' . $order->shipping_lname }}</a>
    </td>
    <td>{{ $record->comment }}</td>
</tr>
