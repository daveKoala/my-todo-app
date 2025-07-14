<?php

namespace App\Console\Commands\ExploreRouteCommand;

use Illuminate\Console\Command;

/**
 * Output Formatting Helper
 * 
 * Handles different output formats for the exploration results.
 * Currently supports:
 * - table: Nice formatted table (default)
 * - json: Machine-readable JSON output
 * - tree: Hierarchical tree view
 * 
 * Easy to extend with additional formats in the future.
 */
class OutputFormatters
{
    private TypeDetectors $typeDetectors;
    
    public function __construct()
    {
        $this->typeDetectors = new TypeDetectors();
    }
    
    /**
     * Main output dispatcher - routes to appropriate formatter
     */
    public function output(array $relationships, string $format, Command $command): void
    {
        match($format) {
            'json' => $this->outputJson($relationships, $command),
            'tree' => $this->outputTree($relationships, $command),
            default => $this->outputTable($relationships, $command)
        };
    }
    
    /**
     * Output results as a formatted table
     * 
     * This is the default format - provides a nice overview of all
     * discovered classes with their types, relationships, and dependencies.
     */
    private function outputTable(array $relationships, Command $command): void
    {
        $command->line('');
        $command->info('📊 Exploration Summary:');
        
        $rows = [];
        foreach ($relationships as $class => $info) {
            $dependencies = isset($info['dependencies']) ? count($info['dependencies']) : 0;
            $implements = isset($info['implements']) ? count($info['implements']) : 0;
            $traits = isset($info['traits']) ? count($info['traits']) : 0;
            
            $rows[] = [
                $info['name'] ?? $class,
                $info['type'] ?? 'Unknown',
                $info['context'] ?? '',
                $info['extends'] ?? '',
                $implements,
                $traits,
                $dependencies
            ];
        }
        
        $command->table([
            'Class',
            'Type',
            'Context',
            'Extends',
            'Interfaces',
            'Traits',
            'Dependencies'
        ], $rows);
    }
    
    /**
     * Output results as JSON
     * 
     * Useful for machine processing or integration with other tools.
     * Provides the complete relationship data structure.
     */
    private function outputJson(array $relationships, Command $command): void
    {
        $command->line(json_encode($relationships, JSON_PRETTY_PRINT));
    }
    
    /**
     * Output results as a hierarchical tree
     * 
     * Shows the exploration depth and relationships in a tree-like format.
     * Useful for understanding the exploration flow and depth.
     */
    private function outputTree(array $relationships, Command $command): void
    {
        $command->info('🌳 Dependency Tree:');
        $this->displayTree($relationships, 0, $command);
    }
    
    /**
     * Recursively display the tree structure
     * 
     * Groups classes by their exploration depth to show the hierarchy.
     */
    private function displayTree(array $relationships, int $depth, Command $command): void
    {
        foreach ($relationships as $class => $info) {
            if (($info['depth'] ?? 0) === $depth) {
                $indent = str_repeat('  ', $depth);
                $emoji = $this->typeDetectors->getClassEmojiFromType($info['type'] ?? '');
                $command->line("{$indent}{$emoji} {$class}");
            }
        }
    }
}