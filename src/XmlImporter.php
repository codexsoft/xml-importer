<?php


namespace CodexSoft\XmlImporter;


class XmlImporter
{

    /**
     * @param string $xml
     *
     * @return array
     * @throws \JsonException
     */
    public function importFromString(string $xml): array
    {
        $xmla = \simplexml_load_string($xml);
        $json = \json_encode($xmla, JSON_THROW_ON_ERROR);
        $array = \json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $array = $this->ensureChildNodesInArrays($array);
        $array = $this->ensureAttributesKeyExists($array);
        return $array;
    }

    /**
     * @param string $filename
     *
     * @return array
     * @throws \JsonException
     */
    public function import(string $filename): array
    {
        return $this->importFromString(\file_get_contents($filename));
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
