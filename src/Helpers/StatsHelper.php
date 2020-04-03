<?php

namespace Lde\ApiHelper\Helpers;

use Illuminate\Support\Facades\Log;
use Lde\ApiHelper\Exceptions\ArraySizeMisMatchException;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus\Exception\MetricsRegistrationException;

class StatsHelper
{
    public static function incHistogram($histogram_name, $qty = 1, array $labels = [], $description = null, array $labelNames = [])
    {
        try {
            $exporter = app('prometheus');

            // Ensure labelNames have env
            if (!in_array('env', $labelNames)) {
                $labelNames[] = 'env';
            }

            // Add env to $labels
            if (!in_array(app()->environment(), $labels)) {
                $labels[] = app()->environment();
            }

            // Overridding prometheus config from api_helper
            $prometheusConfigLables = config('api_helper.prometheus.labels');
            if (!empty($prometheusConfigLables) && is_array($prometheusConfigLables) === true) {
                foreach ($prometheusConfigLables as $key => $row) {
                    $labelNames[] = $key;
                    if (stripos($row, '@') !== false) {
                        // we have an @ - callable
                        $callable = explode('@', $row);
                        if (is_callable($callable)) {
                            $labels[] = call_user_func($callable);
                        } else {
                            $labels[] = $row;
                        }
                    } else {
                        $labels[] = $row;
                    }
                }
            }

            if (count($labels) != count($labelNames)) {
                throw new ArraySizeMisMatchException("{$histogram_name} has mismatch labels and names");
            }

            $histogramBucket = config('api_helper.prometheus.histogram_bucket');
            try {
                $histogram = $exporter->getHistogram($histogram_name);
            } catch (MetricNotFoundException $ex) {
                $histogram = $exporter->registerHistogram($histogram_name, $description, $labelNames, $histogramBucket);
            } catch (MetricsRegistrationException $ex) {
                $histogram = $exporter->registerHistogram($histogram_name, $description, $labelNames, $histogramBucket);
            }

            if ($histogram->getLabelNames()) {
                if (!empty($labels)) {
                    $histogram->observe($qty, $labels);
                } else {
                    $histogram->observe($qty, ['none']);
                }
            } else {
                $histogram->observe($qty);
            }
        } catch (\Exception $ex) {
            Log::error("StatsHelper->incHistogram() threw an exception!!", [
                'error' => $ex->getMessage(),
                'name' => $histogram_name,
                'labels' => $labels,
                'labelNames' => $labelNames,
            ]);
        }
    }
}
