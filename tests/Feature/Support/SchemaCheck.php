<?php

/*
 * A small checker for the part of JSON Schema the support read API contract uses (docs/support-read-api.openapi.yaml), so the contract can be
 * enforced without another dependency. It serves both contracts (the read API, and the hand-off/transcript API in docs/support-agent-api.openapi.yaml). Supported: type (one or a list), enum, properties, required, additionalProperties: false, items, oneOf
 * and local $ref. Anything else in a schema is reported rather than silently ignored, so the contract cannot start using a feature this
 * checker does not understand. The same file and the same rules are kept in modelhub-support (tests/contract.py).
 */

use Symfony\Component\Yaml\Yaml;

const SUPPORT_CONTRACT = __DIR__.'/../../../docs/support-read-api.openapi.yaml';
const SUPPORT_AGENT_CONTRACT = __DIR__.'/../../../docs/support-agent-api.openapi.yaml';

function supportContract(string $file = SUPPORT_CONTRACT): array
{
    return Yaml::parse(file_get_contents($file));
}

function supportSchema(string $name, ?array $contract = null): array
{
    return ($contract ?? supportContract())['components']['schemas'][$name];
}

/** Every way $value does not fit $node; an empty list means it does. @return list<string> */
function schemaProblems(mixed $value, array $node, array $contract, string $path = '$'): array
{
    $unknown = array_diff(array_keys($node), ['type', 'enum', 'properties', 'required', 'additionalProperties', 'items', 'oneOf', '$ref', 'description']);
    if ($unknown !== []) {
        return ["{$path}: the contract uses ".implode(', ', $unknown).', which this checker does not understand'];
    }

    if (isset($node['$ref'])) {
        return schemaProblems($value, $contract['components']['schemas'][str_replace('#/components/schemas/', '', $node['$ref'])], $contract, $path);
    }

    if (isset($node['oneOf'])) {
        foreach ($node['oneOf'] as $option) {
            if (schemaProblems($value, $option, $contract, $path) === []) {
                return [];
            }
        }

        return ["{$path}: fits none of the ".count($node['oneOf']).' allowed shapes'];
    }

    $isList = is_array($value) && array_is_list($value);
    $kinds = [
        'string' => is_string($value), 'integer' => is_int($value), 'boolean' => is_bool($value), 'null' => $value === null,
        'array' => $isList, 'object' => is_array($value) && ! $isList || $value === [] && ($node['type'] ?? null) === 'object',
    ];

    if (isset($node['type'])) {
        $wanted = (array) $node['type'];
        if (! array_filter($wanted, fn ($t) => $kinds[$t])) {
            return ["{$path}: expected ".implode('|', $wanted).', got '.get_debug_type($value)];
        }
    }

    $found = [];
    if (isset($node['enum']) && ! in_array($value, $node['enum'], true)) {
        $found[] = "{$path}: ".json_encode($value).' is not one of '.json_encode($node['enum']);
    }

    if (is_array($value) && ! $isList) {
        $props = $node['properties'] ?? [];
        foreach ($node['required'] ?? [] as $key) {
            array_key_exists($key, $value) || $found[] = "{$path}: missing '{$key}'";
        }
        if (($node['additionalProperties'] ?? true) === false) {
            foreach (array_keys($value) as $key) {
                isset($props[$key]) || $found[] = "{$path}: '{$key}' is not in the contract";
            }
        }
        foreach ($props as $key => $sub) {
            if (array_key_exists($key, $value)) {
                $found = array_merge($found, schemaProblems($value[$key], $sub, $contract, "{$path}.{$key}"));
            }
        }
    }

    if ($isList && isset($node['items'])) {
        foreach ($value as $i => $item) {
            $found = array_merge($found, schemaProblems($item, $node['items'], $contract, "{$path}[{$i}]"));
        }
    }

    return $found;
}
