<?php


namespace CodexSoft\XmlImporter;


class XmlImporter
{
    public function import(string $filename): array
    {
        $xmla = simplexml_load_string(\file_get_contents($filename));
        $json = json_encode($xmla);
        $array = json_decode($json, true);
        $array = $this->ensureChildNodesInArrays($array);
        $array = $this->ensureAttributesKeyExists($array);
        return $array;
    }

    private function isArrayAssoc(array $arr): bool
    {
        if (array() === $arr) {
            return false;
        }
        return \array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function ensureAttributesKeyExists(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {

            $fixedValue = $value;

            if (\is_array($value)) {
                $fixedValue = $this->ensureAttributesKeyExists($value);
            }

            // ensure that attributes key exists
            if ($key !== '@attributes' && \is_array($fixedValue) && !\array_key_exists('@attributes', $fixedValue) && $this->isArrayAssoc($fixedValue)) {
                $fixedValue['@attributes'] = [];
            }

            $result[$key] = $fixedValue;
        }

        return $result;
    }

    private function ensureChildNodesInArrays(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {

            // skipping comments
            if ($key === 'comment') {
                continue;
            }

            $fixedValue = $value;

            if (\is_array($value)) {

                // checking descendants
                $fixedValue = $this->ensureChildNodesInArrays($value);

                // fixing single tag childs - wrapping them into array
                if (!\is_int($key) && \array_key_exists('@attributes', $value)) {
                    $result[$key][] = $fixedValue;
                    continue;
                }

            }

            $result[$key] = $fixedValue;
        }
        return $result;
    }

}
