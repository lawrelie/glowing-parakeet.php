<?php
namespace Lawrelie\GlowingParakeet\Contents\SearchResults;
use Lawrelie\GlowingParakeet as lgp;
use DomainException, Throwable;
class SearchResult extends lgp\Contents\Contents {
    private array $children = [];
    public function __get(string $name): mixed {
        return 'children' === $name ? $this->$name : parent::__get($name);
    }
    public function createChild(mixed $name, ?string $className = self::class): parent {
        $result = parent::createChild(['name' => $name], $className);
        if ($result instanceof self) {
            return $result;
        }
        throw new DomainException;
    }
    public function normalizeSearchQuery(string $query, string $quote = '["\'(]'): array {
        $before = '';
        while ('' !== $quote && !!\preg_match(\sprintf('/(?<=^|\s)(?:%s)/u', $quote), $query, $m, \PREG_OFFSET_CAPTURE)) {
            list($pattern, $newFormat, $newQuote) = match ($m[0][0]) {
                '(' => ['/(?<=^|\s|\()\((?:((?>[^()]+)|(?R))*)\)(?=\)|\s|$)/u', '(%s)', $quote],
                default => [\sprintf('/^%s[\s\S]*?%s(?=\s|$)/u', \preg_quote($m[0][0], '/')), '%s', ''],
            };
            $before .= \substr($query, 0, $m[0][1]);
            $query = \substr($query, $m[0][1]);
            $quoteLength = \strlen($m[0][0]);
            if (!\preg_match($pattern, $query, $mm, \PREG_OFFSET_CAPTURE) || 0 !== $mm[0][1]) {
                $before .= $m[0][0];
                $query = \substr($query, $quoteLength);
                continue;
            }
            $beforeResult = $this->normalizeSearchQuery($before, '');
            $result = $this->normalizeSearchQuery(\trim(\substr($mm[0][0], $quoteLength, \strlen($mm[0][0]) - $quoteLength * 2)), $newQuote);
            $afterResult = $this->normalizeSearchQuery(\substr($query, $mm[0][1]), $quote);
            $operator = function(string $statement, string $name): string {
                $strWidth = \sprintf('\str_%s_with', $name);
                return match (true) {'' === $statement => '', $strWidth($statement, 'AND'), $strWidth($statement, 'OR') => ' ', default => ' AND '};
            };
            return [
                'statement' =>
                    $beforeResult['statement']
                    . $operator($beforeResult['statement'], 'ends')
                    . \sprintf($newFormat, $result['statement'])
                    . $operator($afterResult['statement'], 'starts')
                    . $afterResult['statement'],
                'parameters' => $beforeResult['parameters'] + $result['parameters'] + $afterResult['parameters'],
            ];
        }
        static $i = 0;
        $query = $before . $query;
        $words = \preg_split('/\s+/u', '' === $quote ? $this->normalizeQuery($query) : $query, flags: \PREG_SPLIT_NO_EMPTY);
        if (!$words) {
            return ['statement' => '', 'parameters' => []];
        }
        $isNot = false;
        $operator = '';
        $statement = '';
        $parameters = [];
        foreach ($words as $v) {
            $v = 'AND' === $v && '' === $statement ? $this->normalizeQuery($v) : $v;
            if (('AND' === $v || 'OR' === $v) && !$isNot && '' === $operator) {
                $operator = $v;
                continue;
            } elseif ('NOT' === $v && !$isNot) {
                $isNot = true;
                continue;
            }
            $i++;
            $word = $this->normalizeQuery($v);
            list($like, $or, $value) = !$isNot ? ['LIKE', 'OR', '%' . \addcslashes($word, '%_\\') . '%'] : ['IS NOT', 'AND', $word];
            $operator = '' === $operator ? 'AND' : $operator;
            $parameter = ":lgp_search_query_$i";
            $statement .= ('' === $statement ? '' : " $operator ") . "(lgp_name $like $parameter $or lgp_content $like $parameter $or lgp_description $like $parameter)";
            $parameters[$parameter] = $value;
            $isNot = false;
            $operator = '';
        }
        return ['statement' => $statement, 'parameters' => $parameters];
    }
    public function query(string $query): ?parent {
        try {
            return $this->children[$this->name];
        } catch (Throwable) {}
        try {
            $child = $this->createChild($query);
        } catch (Throwable) {
            return null;
        }
        $this->children[$child->name] = $child;
        return $child;
    }
    protected function readProperty_description(mixed $var): string {
        return $this->parakeet->format['queryResult'](\count($this->query));
    }
    protected function readProperty_id(mixed $var): lgp\Contents\Properties\Id {
        return parent::readProperty_id(\md5($this->name));
    }
    protected function readProperty_query(): array {
        $query = $this->searchQuery;
        try {
            $select = $this->parakeet->db->prepare(\sprintf('SELECT lgp_id FROM lgp_contents WHERE %s ORDER BY lgp_mtime DESC', $query['statement']));
            $select->execute($query['parameters']);
            return $select->fetchAll();
        } catch (Throwable) {}
        return [];
    }
    protected function readProperty_queryKey(): string {
        return $this->parakeet->queryKeys['search'];
    }
    protected function readProperty_searchQuery(): array {
        $query = $this->normalizeSearchQuery($this->name);
        $trimPatterns = ['/^(?:AND|OR)\s+|\s+(?:AND|OR)$/u', ''];
        $query['statement'] = \preg_replace(...[...$trimPatterns, $query['statement']]);
        try {
            $parent = $this->parent->searchQuery;
            $parent['statement'] = \preg_replace(...[...$trimPatterns, $parent['statement']]);
            $query['statement'] = match (true) {
                '' === $query['statement'] => $parent['statement'],
                '' === $parent['statement'] => $query['statement'],
                default => \sprintf('(%s) AMD (%s)', $parent['statement'], $query['statement']),
            };
            $query['parameters'] += $parent['parameters'];
        } catch (Throwable) {}
        return $query;
    }
}
