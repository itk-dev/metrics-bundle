<?php

namespace ItkDev\MetricsBundle\Controller;

use ItkDev\MetricsBundle\Service\MetricsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MetricsController extends AbstractController
{
    public function __construct(
        private readonly MetricsService $metricsService,
    ) {
    }

    /**
     * Render metrics collected by the application.
     *
     * @return Response
     *   HTTP response to send back to the client
     */
    public function metrics(): Response
    {
        if ($this->metricsService->isExtensionOpcacheEnabled()) {
            $this->opcacheMetrics();
        }

        if ($this->metricsService->isExtensionApcuEnabled()) {
            $this->apcuMetrics();
        }

        return new Response($this->metricsService->render(), Response::HTTP_OK, ['content-type' => 'text/plain']);
    }

    /**
     * Get opcache statistics.
     *
     * @return void
     */
    private function opcacheMetrics(): void
    {
        if (!ini_get('opcache.enable')) {
            // Opcache was not enabled.
            $this->metricsService->gauge('php_opcache_enabled', 'opcache enabled', 0);

            return;
        }

        $status = opcache_get_status();

        // Basic information.
        $this->metricsService->gauge('php_opcache_enabled', 'opcache enabled', (int) $status['opcache_enabled']);
        $this->metricsService->gauge('php_opcache_full', 'Is opcache full', (int) $status['cache_full']);
        $this->metricsService->gauge('php_opcache_restart_pending', 'Is opcache restart pending', (int) $status['restart_pending']);
        $this->metricsService->gauge('php_opcache_restart_in_progress', 'Is opcache restart in progress', (int) $status['restart_in_progress']);

        // Memory usage.
        $this->metricsService->gauge('php_opcache_used_memory_bytes', 'Used memory', $status['memory_usage']['used_memory']);
        $this->metricsService->gauge('php_opcache_free_memory_bytes', 'Used memory', $status['memory_usage']['free_memory']);
        $this->metricsService->gauge('php_opcache_wasted_memory_bytes', 'Used memory', $status['memory_usage']['wasted_memory']);
        $this->metricsService->gauge('php_opcache_current_wasted_ratio', 'Used memory', $status['memory_usage']['current_wasted_percentage'] / 100);

        // Statistics information.
        $this->metricsService->gauge('php_opcache_num_cached_scripts_total', '', $status['opcache_statistics']['num_cached_scripts']);
        $this->metricsService->gauge('php_opcache_num_cached_keys_total', '', $status['opcache_statistics']['num_cached_keys']);
        $this->metricsService->gauge('php_opcache_max_cached_keys_total', '', $status['opcache_statistics']['max_cached_keys']);
        $this->metricsService->gauge('php_opcache_hits_total', '', $status['opcache_statistics']['hits']);
        $this->metricsService->gauge('php_opcache_start_time_duration_seconds', '', $status['opcache_statistics']['start_time']);
        $this->metricsService->gauge('php_opcache_last_restart_time_duration_seconds', '', $status['opcache_statistics']['last_restart_time']);
        $this->metricsService->gauge('php_opcache_oom_restarts_total', '', $status['opcache_statistics']['oom_restarts']);
        $this->metricsService->gauge('php_opcache_hash_restarts_total', '', $status['opcache_statistics']['hash_restarts']);
        $this->metricsService->gauge('php_opcache_manual_restarts_total', '', $status['opcache_statistics']['manual_restarts']);
        $this->metricsService->gauge('php_opcache_misses_total', '', $status['opcache_statistics']['misses']);
        $this->metricsService->gauge('php_opcache_blacklist_misses_total', '', $status['opcache_statistics']['blacklist_misses']);
        $this->metricsService->gauge('php_opcache_blacklist_miss_ratio_rate', '', $status['opcache_statistics']['blacklist_miss_ratio']);
        $this->metricsService->gauge('php_opcache_opcache_hit_rate', '', $status['opcache_statistics']['opcache_hit_rate']);
    }

    /**
     * Get APCu statistics.
     *
     * @return void
     */
    private function apcuMetrics(): void
    {
        if (!ini_get('apc.enabled')) {
            // APCu was not enabled.
            $this->metricsService->gauge('php_apcu_enabled', 'APC enabled', 0);

            return;
        }

        $status = apcu_cache_info();

        $this->metricsService->gauge('php_apcu_enabled', 'APC enabled', 1);
        $this->metricsService->gauge('php_apcu_num', '', $status['num_slots']);
        $this->metricsService->gauge('php_apcu_num_hits_total', '', $status['num_hits']);
        $this->metricsService->gauge('php_apcu_num_misses_total', '', $status['num_misses']);
        $this->metricsService->gauge('php_apcu_num_inserts_total', '', $status['num_inserts']);
        $this->metricsService->gauge('php_apcu_num_entries_total', '', $status['num_entries']);
        $this->metricsService->gauge('php_apcu_expunges_total', '', $status['expunges']);
        $this->metricsService->gauge('php_apcu_start_time', '', $status['start_time']);
        $this->metricsService->gauge('php_apcu_mem_size_bytes', '', $status['mem_size']);
    }
}
