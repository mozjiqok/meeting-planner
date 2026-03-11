@extends('admin.layout')
@section('title', 'Дашборд')

@section('content')
<h1 class="section-heading">📊 Дашборд</h1>

<div class="stats">
    <div class="stat">
        <div class="stat-value">{{ $upcomingBookings->count() }}</div>
        <div class="stat-label">Предстоящих записей</div>
    </div>
    <div class="stat">
        <div class="stat-value">{{ $slots->count() }}</div>
        <div class="stat-label">Активных слотов</div>
    </div>
    <div class="stat">
        <div class="stat-value">{{ $upcomingBookings->where('booking_date', today(config('app.timezone'))->toDateString())->count() }}</div>
        <div class="stat-label">Сегодня</div>
    </div>
</div>

<div class="card">
    <div class="card-title">📅 Предстоящие записи</div>
    @if($upcomingBookings->isEmpty())
        <div class="empty">Нет предстоящих записей</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Дата и время</th>
                    <th>Подписчик</th>
                    <th>Проблема</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($upcomingBookings as $booking)
                <tr>
                    <td>
                        <strong>{{ $booking->call_datetime->locale('ru')->isoFormat('D MMM') }}</strong>
                        <span class="text-muted"> {{ $booking->call_datetime->format('H:i') }}</span>
                    </td>
                    <td>
                        @if($booking->telegram_username)
                            <a href="https://t.me/{{ $booking->telegram_username }}" target="_blank" style="color: var(--accent); text-decoration:none;">@{{ $booking->telegram_username }}</a>
                        @else
                            {{ $booking->telegram_first_name ?? '—' }}
                        @endif
                    </td>
                    <td style="max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                        title="{{ $booking->answers['q1'] ?? '' }}">
                        {{ Str::limit($booking->answers['q1'] ?? '—', 60) }}
                    </td>
                    <td>
                        <a href="{{ route('admin.bookings') }}" class="btn btn-ghost btn-sm">Детали</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
