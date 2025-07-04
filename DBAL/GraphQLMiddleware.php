<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\FilterOp;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\AST;
use PDO;

/**
 * Middleware that executes GraphQL queries and mutations on a Crud instance.
 */
class GraphQLMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
    private ?Crud $crud = null;
    private Schema $schema;
    private ScalarType $jsonType;
    private array $columns = [];

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    /**
     * Attach the middleware to a Crud instance and build the schema.
     */
    public function attach(Crud $crud): Crud
    {
        $crud = $crud->withMiddleware($this);
        $this->crud = $crud;

        $pdo = (function () { return $this->connection; })->call($crud);
        $tables = (function () { return $this->tables; })->call($crud);
        $table = $tables[0] ?? null;
        if ($table !== null) {
            $this->columns = $this->fetchColumns($pdo, $table);
        }
        $this->buildSchema();
        return $crud;
    }

    /**
     * Execute a GraphQL query string and return the result as an array.
     */
    public function handle(string $request): array
    {
        if (!$this->crud) {
            throw new \LogicException('GraphQLMiddleware not attached to Crud');
        }
        $result = GraphQL::executeQuery($this->schema, $request);
        return $result->toArray();
    }

    private function fetchColumns(PDO $pdo, string $table): array
    {
        $stm = $pdo->query("SELECT * FROM {$table} LIMIT 0");
        $cols = [];
        if ($stm) {
            $count = $stm->columnCount();
            for ($i = 0; $i < $count; $i++) {
                $meta = $stm->getColumnMeta($i);
                if (isset($meta['name'])) {
                    $cols[] = $meta['name'];
                }
            }
        }
        return $cols;
    }

    private function buildSchema(): void
    {
        $this->jsonType = new ScalarType([
            'name' => 'JSON',
            'serialize' => fn($v) => $v,
            'parseValue' => fn($v) => $v,
            'parseLiteral' => fn($v) => AST::valueFromASTUntyped($v),
        ]);

        $recordFields = [];
        foreach ($this->columns as $c) {
            $recordFields[$c] = ['type' => Type::string()];
        }
        $recordType = new ObjectType([
            'name' => 'Record',
            'fields' => $recordFields,
        ]);

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'read' => [
                    'type' => Type::listOf($recordType),
                    'args' => [
                        'filter' => ['type' => $this->jsonType],
                    ],
                    'resolve' => function ($root, array $args) {
                        $c = $this->crud;
                        if (isset($args['filter'])) {
                            $c = $c->where($args['filter']);
                        }
                        return iterator_to_array($c->select(...$this->columns));
                    },
                ],
            ],
        ]);

        $mutationType = new ObjectType([
            'name' => 'Mutation',
            'fields' => [
                'insert' => [
                    'type' => Type::int(),
                    'args' => [
                        'data' => ['type' => $this->jsonType],
                    ],
                    'resolve' => function ($root, array $args) {
                        return (int)$this->crud->insert((array)$args['data']);
                    },
                ],
                'update' => [
                    'type' => Type::int(),
                    'args' => [
                        'id' => Type::nonNull(Type::int()),
                        'data' => ['type' => $this->jsonType],
                    ],
                    'resolve' => function ($root, array $args) {
                        return $this->crud
                            ->where(['id' => [FilterOp::EQ, $args['id']]])
                            ->update((array)$args['data']);
                    },
                ],
                'delete' => [
                    'type' => Type::int(),
                    'args' => [
                        'id' => Type::nonNull(Type::int()),
                    ],
                    'resolve' => function ($root, array $args) {
                        return $this->crud
                            ->where(['id' => [FilterOp::EQ, $args['id']]])
                            ->delete();
                    },
                ],
            ],
        ]);

        $this->schema = new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
        ]);
    }
}
