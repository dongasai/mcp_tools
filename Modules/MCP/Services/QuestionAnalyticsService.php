<?php

namespace Modules\MCP\Services;

use Modules\MCP\Models\AgentQuestion;
use Modules\MCP\Models\Agent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Agent问题分析服务
 * 
 * 提供问题统计、分析和报告功能
 */
class QuestionAnalyticsService
{
    /**
     * 获取问题类型统计
     */
    public function getQuestionTypeStats(array $filters = []): array
    {
        $query = AgentQuestion::query();
        
        // 应用过滤条件
        $this->applyFilters($query, $filters);
        
        $stats = $query->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->keyBy('type')
            ->map(fn($item) => $item->count)
            ->toArray();
            
        return [
            'CHOICE' => $stats['CHOICE'] ?? 0,
            'FEEDBACK' => $stats['FEEDBACK'] ?? 0,
            'total' => array_sum($stats),
        ];
    }
    
    /**
     * 获取问题状态统计
     */
    public function getQuestionStatusStats(array $filters = []): array
    {
        $query = AgentQuestion::query();
        
        $this->applyFilters($query, $filters);
        
        $stats = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(fn($item) => $item->count)
            ->toArray();
            
        return [
            'PENDING' => $stats['PENDING'] ?? 0,
            'ANSWERED' => $stats['ANSWERED'] ?? 0,
            'IGNORED' => $stats['IGNORED'] ?? 0,
            'total' => array_sum($stats),
        ];
    }
    
    /**
     * 获取问题优先级统计
     */
    public function getQuestionPriorityStats(array $filters = []): array
    {
        $query = AgentQuestion::query();
        
        $this->applyFilters($query, $filters);
        
        $stats = $query->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->keyBy('priority')
            ->map(fn($item) => $item->count)
            ->toArray();
            
        return [
            'URGENT' => $stats['URGENT'] ?? 0,
            'HIGH' => $stats['HIGH'] ?? 0,
            'MEDIUM' => $stats['MEDIUM'] ?? 0,
            'LOW' => $stats['LOW'] ?? 0,
            'total' => array_sum($stats),
        ];
    }
    
    /**
     * 获取回答时间分析
     */
    public function getResponseTimeAnalysis(array $filters = []): array
    {
        $query = AgentQuestion::query()
            ->whereNotNull('answered_at');

        $this->applyFilters($query, $filters);

        // 获取所有已回答的问题
        $questions = $query->get(['created_at', 'answered_at']);

        if ($questions->isEmpty()) {
            return [
                'average_response_time_minutes' => 0,
                'fastest_response_minutes' => 0,
                'slowest_response_minutes' => 0,
                'total_answered_questions' => 0,
            ];
        }

        // 计算响应时间（分钟）
        $responseTimes = $questions->map(function ($question) {
            return $question->created_at->diffInMinutes($question->answered_at);
        });

        return [
            'average_response_time_minutes' => round($responseTimes->avg(), 2),
            'fastest_response_minutes' => $responseTimes->min(),
            'slowest_response_minutes' => $responseTimes->max(),
            'total_answered_questions' => $questions->count(),
        ];
    }
    
    /**
     * 获取Agent提问模式分析
     */
    public function getAgentQuestionPatterns(array $filters = []): Collection
    {
        $query = AgentQuestion::query()
            ->with('agent:id,name,agent_id')
            ->select(
                'agent_id',
                DB::raw('COUNT(*) as total_questions'),
                DB::raw('AVG(CASE WHEN status = "ANSWERED" THEN 1 ELSE 0 END) * 100 as answer_rate'),
                DB::raw('AVG(CASE WHEN priority = "URGENT" THEN 1 ELSE 0 END) * 100 as urgent_rate'),
                DB::raw('COUNT(CASE WHEN status = "PENDING" THEN 1 END) as pending_questions')
            )
            ->groupBy('agent_id')
            ->orderByDesc('total_questions');
            
        $this->applyFilters($query, $filters);
        
        return $query->get()->map(function ($item) {
            return [
                'agent_id' => $item->agent_id,
                'agent_name' => $item->agent->name ?? 'Unknown',
                'agent_identifier' => $item->agent->agent_id ?? 'Unknown',
                'total_questions' => $item->total_questions,
                'answer_rate' => round($item->answer_rate, 2),
                'urgent_rate' => round($item->urgent_rate, 2),
                'pending_questions' => $item->pending_questions,
            ];
        });
    }
    
    /**
     * 获取用户响应效率分析
     */
    public function getUserResponseEfficiency(array $filters = []): array
    {
        $query = AgentQuestion::query()
            ->whereNotNull('answered_at');

        $this->applyFilters($query, $filters);

        // 获取所有已回答的问题
        $questions = $query->get(['created_at', 'answered_at']);

        // 按小时分组统计
        $hourlyData = [];
        $weeklyData = [];

        foreach ($questions as $question) {
            $responseTime = $question->created_at->diffInMinutes($question->answered_at);
            $hour = $question->answered_at->hour;
            $dayOfWeek = $question->answered_at->dayOfWeek + 1; // 1-7 (Sunday=1)

            // 小时统计
            if (!isset($hourlyData[$hour])) {
                $hourlyData[$hour] = ['count' => 0, 'total_time' => 0];
            }
            $hourlyData[$hour]['count']++;
            $hourlyData[$hour]['total_time'] += $responseTime;

            // 星期统计
            if (!isset($weeklyData[$dayOfWeek])) {
                $weeklyData[$dayOfWeek] = ['count' => 0, 'total_time' => 0];
            }
            $weeklyData[$dayOfWeek]['count']++;
            $weeklyData[$dayOfWeek]['total_time'] += $responseTime;
        }

        // 计算平均值
        $hourlyStats = collect($hourlyData)->map(function ($data, $hour) {
            return (object) [
                'hour' => $hour,
                'count' => $data['count'],
                'avg_response_time' => $data['count'] > 0 ? round($data['total_time'] / $data['count'], 2) : 0,
            ];
        });

        $weeklyStats = collect($weeklyData)->map(function ($data, $day) {
            return (object) [
                'day_of_week' => $day,
                'count' => $data['count'],
                'avg_response_time' => $data['count'] > 0 ? round($data['total_time'] / $data['count'], 2) : 0,
            ];
        });

        return [
            'hourly_distribution' => $this->formatHourlyStats($hourlyStats),
            'weekly_distribution' => $this->formatWeeklyStats($weeklyStats),
        ];
    }
    
    /**
     * 获取问题趋势分析
     */
    public function getQuestionTrends(int $days = 30, array $filters = []): Collection
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        
        $query = AgentQuestion::query()
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN status = "ANSWERED" THEN 1 END) as answered'),
                DB::raw('COUNT(CASE WHEN status = "PENDING" THEN 1 END) as pending'),
                DB::raw('COUNT(CASE WHEN priority = "URGENT" THEN 1 END) as urgent')
            )
            ->groupBy('date')
            ->orderBy('date');
            
        $this->applyFilters($query, $filters);
        
        return $query->get();
    }
    
    /**
     * 获取综合分析报告
     */
    public function getComprehensiveReport(array $filters = []): array
    {
        return [
            'overview' => [
                'type_stats' => $this->getQuestionTypeStats($filters),
                'status_stats' => $this->getQuestionStatusStats($filters),
                'priority_stats' => $this->getQuestionPriorityStats($filters),
            ],
            'performance' => [
                'response_time' => $this->getResponseTimeAnalysis($filters),
                'user_efficiency' => $this->getUserResponseEfficiency($filters),
            ],
            'patterns' => [
                'agent_patterns' => $this->getAgentQuestionPatterns($filters)->take(10),
                'trends' => $this->getQuestionTrends(30, $filters),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }
    
    /**
     * 应用过滤条件
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }
        
        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
    }
    
    /**
     * 格式化小时统计数据
     */
    private function formatHourlyStats(Collection $stats): array
    {
        $formatted = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $stat = $stats->get($hour);
            $formatted[] = [
                'hour' => $hour,
                'count' => $stat->count ?? 0,
                'avg_response_time' => round($stat->avg_response_time ?? 0, 2),
            ];
        }
        return $formatted;
    }
    
    /**
     * 格式化星期统计数据
     */
    private function formatWeeklyStats(Collection $stats): array
    {
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $formatted = [];
        
        for ($day = 1; $day <= 7; $day++) {
            $stat = $stats->get($day);
            $formatted[] = [
                'day_of_week' => $day,
                'day_name' => $dayNames[$day - 1],
                'count' => $stat->count ?? 0,
                'avg_response_time' => round($stat->avg_response_time ?? 0, 2),
            ];
        }
        return $formatted;
    }
}
