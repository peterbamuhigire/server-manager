<?php

/**
 * SystemMetric Model
 *
 * Handles system performance metrics storage and retrieval.
 * Stores CPU, RAM, Disk usage percentages for dashboard display.
 *
 * @package ChweziSM
 * @subpackage Models
 * @version 1.0
 */

class SystemMetric extends Model
{
    protected $table = 'system_metrics';

    /**
     * Record a system metric
     *
     * @param string $type Metric type (cpu, ram, disk)
     * @param float $value Metric value (percentage)
     * @return bool Success status
     */
    public static function record($type, $value)
    {
        $db = db();
        $stmt = $db->prepare("
            INSERT INTO system_metrics (metric_type, metric_value, recorded_at)
            VALUES (:type, :value, NOW())
        ");

        return $stmt->execute([
            'type' => $type,
            'value' => round($value, 2)
        ]);
    }

    /**
     * Get latest metric value for a specific type
     *
     * @param string $type Metric type (cpu, ram, disk)
     * @return float|null Latest metric value or null
     */
    public static function getLatest($type)
    {
        $db = db();
        $stmt = $db->prepare("
            SELECT metric_value
            FROM system_metrics
            WHERE metric_type = :type
            ORDER BY recorded_at DESC
            LIMIT 1
        ");

        $stmt->execute(['type' => $type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (float)$result['metric_value'] : null;
    }

    /**
     * Get all latest metrics (cpu, ram, disk)
     *
     * @return array Associative array of latest metrics
     */
    public static function getAllLatest()
    {
        $metrics = [];
        $types = ['cpu', 'ram', 'disk'];

        foreach ($types as $type) {
            $metrics[$type] = self::getLatest($type) ?? 0;
        }

        return $metrics;
    }

    /**
     * Get historical metrics for a specific type
     *
     * @param string $type Metric type
     * @param int $limit Number of records to retrieve
     * @return array Array of metrics with timestamps
     */
    public static function getHistory($type, $limit = 100)
    {
        $db = db();
        $stmt = $db->prepare("
            SELECT metric_value, recorded_at
            FROM system_metrics
            WHERE metric_type = :type
            ORDER BY recorded_at DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get average metric value for a specific period
     *
     * @param string $type Metric type
     * @param string $period Period (1h, 24h, 7d)
     * @return float|null Average value
     */
    public static function getAverage($type, $period = '1h')
    {
        $intervals = [
            '1h' => 'INTERVAL 1 HOUR',
            '24h' => 'INTERVAL 24 HOUR',
            '7d' => 'INTERVAL 7 DAY',
            '30d' => 'INTERVAL 30 DAY'
        ];

        $interval = $intervals[$period] ?? $intervals['1h'];

        $db = db();
        $stmt = $db->prepare("
            SELECT AVG(metric_value) as avg_value
            FROM system_metrics
            WHERE metric_type = :type
            AND recorded_at >= NOW() - $interval
        ");

        $stmt->execute(['type' => $type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && $result['avg_value'] !== null ?
            round((float)$result['avg_value'], 2) : null;
    }

    /**
     * Clean old metrics (older than specified days)
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted rows
     */
    public static function cleanOld($days = 30)
    {
        $db = db();
        $stmt = $db->prepare("
            DELETE FROM system_metrics
            WHERE recorded_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");

        $stmt->execute(['days' => $days]);
        return $stmt->rowCount();
    }

    /**
     * Get metric statistics
     *
     * @param string $type Metric type
     * @param string $period Period for statistics
     * @return array Statistics (min, max, avg)
     */
    public static function getStats($type, $period = '24h')
    {
        $intervals = [
            '1h' => 'INTERVAL 1 HOUR',
            '24h' => 'INTERVAL 24 HOUR',
            '7d' => 'INTERVAL 7 DAY'
        ];

        $interval = $intervals[$period] ?? $intervals['24h'];

        $db = db();
        $stmt = $db->prepare("
            SELECT
                MIN(metric_value) as min_value,
                MAX(metric_value) as max_value,
                AVG(metric_value) as avg_value
            FROM system_metrics
            WHERE metric_type = :type
            AND recorded_at >= NOW() - $interval
        ");

        $stmt->execute(['type' => $type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'min' => $result['min_value'] ? round((float)$result['min_value'], 2) : 0,
            'max' => $result['max_value'] ? round((float)$result['max_value'], 2) : 0,
            'avg' => $result['avg_value'] ? round((float)$result['avg_value'], 2) : 0
        ];
    }
}
