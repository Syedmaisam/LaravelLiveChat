@extends('layouts.dashboard')

@section('title', 'Analytics')

@section('content')
<div class="flex-1 flex flex-col bg-[#0D0D0D] overflow-auto">
    <!-- Header -->
    <div class="bg-[#1A1A1A] border-b border-[#2A2A2A] px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-white">Analytics Overview</h1>
                <p class="text-sm text-gray-500">Track your chat performance</p>
            </div>
            <div class="flex space-x-2">
                <a href="?range=7" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all {{ $range == 7 ? 'bg-[#D4AF37]/10 text-[#D4AF37]' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">7 Days</a>
                <a href="?range=30" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all {{ $range == 30 ? 'bg-[#D4AF37]/10 text-[#D4AF37]' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">30 Days</a>
            </div>
        </div>
    </div>

    <div class="p-6">
        <!-- Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                <div class="text-sm text-gray-500 mb-1">Total Chats</div>
                <div class="text-2xl font-bold text-white">{{ $chatsPerDay->sum('total') }}</div>
                <div class="text-xs text-emerald-400 mt-1">↑ 12%</div>
            </div>
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                <div class="text-sm text-gray-500 mb-1">Avg Response</div>
                <div class="text-2xl font-bold text-white">{{ $avgResponseTime }}s</div>
                <div class="text-xs text-emerald-400 mt-1">↓ 5%</div>
            </div>
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                <div class="text-sm text-gray-500 mb-1">Total Visitors</div>
                <div class="text-2xl font-bold text-white">{{ $totalVisitors }}</div>
            </div>
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                <div class="text-sm text-gray-500 mb-1">Satisfaction</div>
                <div class="text-2xl font-bold text-[#D4AF37]">4.8/5</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Chart -->
            <div class="lg:col-span-2 bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                <h3 class="font-semibold text-white mb-4">Chat Volume</h3>
                <canvas id="chatChart" height="200"></canvas>
            </div>

            <!-- Leaderboard -->
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                <h3 class="font-semibold text-white mb-4">Top Agents</h3>
                <div class="space-y-3">
                    @foreach($agentStats as $index => $stat)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="text-gray-500 text-sm w-4">#{{ $index + 1 }}</span>
                            <span class="text-white text-sm">{{ $stat->name }}</span>
                        </div>
                        <span class="bg-[#D4AF37]/10 text-[#D4AF37] px-2 py-0.5 rounded text-xs font-medium">{{ $stat->chat_count }}</span>
                    </div>
                    @endforeach
                    @if($agentStats->isEmpty())
                        <p class="text-gray-500 text-sm text-center py-4">No data</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('chatChart');
    const chartData = @json($chatsPerDay);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(d => d.date),
            datasets: [{
                label: 'Chats',
                data: chartData.map(d => d.total),
                borderColor: '#D4AF37',
                backgroundColor: 'rgba(212, 175, 55, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#2A2A2A' }, ticks: { color: '#666' } },
                x: { grid: { display: false }, ticks: { color: '#666' } }
            }
        }
    });
</script>
@endpush
@endsection
