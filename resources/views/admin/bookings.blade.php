@extends('admin.layout')
@section('title', 'Записи')

@section('content')
<h1 class="section-heading">📋 Записи на звонки</h1>

{{-- Upcoming bookings --}}
<div class="card">
    <div class="card-title">Предстоящие</div>
    @if($upcoming->isEmpty())
    <div class="empty">Нет предстоящих записей</div>
    @else
    @foreach($upcoming as $booking)
    <div style="border: 1px solid var(--border); border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem; background: var(--bg2);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap;">
            <div>
                <div style="font-size:1.05rem; font-weight:700; margin-bottom:.4rem;">
                    🗓 {{ $booking->call_datetime->locale('ru')->isoFormat('dddd, D MMMM [в] HH:mm') }}
                </div>
                <div style="color:var(--muted); font-size:.875rem;">
                    👤
                    @if($booking->telegram_username)
                    <a href="https://t.me/{{ $booking->telegram_username }}" target="_blank" style="color:var(--accent); text-decoration:none;">{{ '@' . $booking->telegram_username }}</a>
                    @else
                    {{ $booking->telegram_first_name }}
                    @endif
                    · ID: {{ $booking->telegram_user_id }}
                </div>
            </div>
            <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
                {{-- Update meeting URL --}}
                <form method="POST" action="{{ route('admin.bookings.url', $booking) }}" style="display:flex;gap:.5rem;">
                    @csrf @method('PATCH')
                    <input type="url" name="meeting_url" value="{{ $booking->meeting_url }}" placeholder="Ссылка на встречу"
                        style="width:220px; font-size:.8rem; padding:.35rem .6rem;">
                    <button type="submit" class="btn btn-ghost btn-sm">💾</button>
                </form>
                {{-- Cancel --}}
                <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}"
                    onsubmit="return confirm('Отменить запись и уведомить пользователя?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Отменить</button>
                </form>
            </div>
        </div>

        {{-- Answers --}}
        <div style="margin-top:1rem; display:grid; gap:.6rem;">
            <div>
                <span class="text-muted">Вопрос:</span><br>
                {{ $booking->answers['q1'] ?? '—' }}
            </div>
        </div>

        @if($booking->meeting_url)
        <div style="margin-top:.75rem;">
            🔗 <a href="{{ $booking->meeting_url }}" target="_blank" style="color:var(--accent); font-size:.875rem;">{{ $booking->meeting_url }}</a>
        </div>
        @endif
    </div>
    @endforeach
    @endif
</div>

{{-- Past / cancelled --}}
@if($past->isNotEmpty())
<div class="card">
    <div class="card-title" style="color:var(--muted);">Прошедшие и отменённые (последние 50)</div>
    <table>
        <thead>
            <tr>
                <th>Дата</th>
                <th>Подписчик</th>
                <th>Статус</th>
            </tr>
        </thead>
        <tbody>
            @foreach($past as $booking)
            <tr>
                <td>{{ $booking->call_datetime->locale('ru')->isoFormat('D MMM YYYY, HH:mm') }}</td>
                <td>
                    @if($booking->telegram_username) {{ '@' . $booking->telegram_username }}
                    @else {{ $booking->telegram_first_name }}
                    @endif
                </td>
                <td>
                    @if($booking->status === 'confirmed')
                    <span class="badge badge-green">Состоялся</span>
                    @else
                    <span class="badge badge-red">Отменён</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection