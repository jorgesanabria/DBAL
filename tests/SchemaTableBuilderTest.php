<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Schema\SchemaTableBuilder;
use DBAL\Platform\SqlitePlatform;

class SchemaTableBuilderTest extends TestCase
{
    public function testLambdaColumnDefinition()
    {
        $table = new SchemaTableBuilder('users', new SqlitePlatform());
        $table->column('id', function ($c) {
            $c->integer()->primaryKey()->autoIncrement();
        });
        $table->column('name', function ($c) {
            $c->text();
        });
        $this->assertEquals(
            'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)',
            $table->build()
        );
    }

    public function testAddColumnWithStringType()
    {
        $table = new SchemaTableBuilder('items', new SqlitePlatform());
        $table->addColumn('id', function ($c) {
            $c->integer()->primaryKey();
        });
        $table->addColumn('name', 'TEXT');
        $this->assertEquals(
            'CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT)',
            $table->build()
        );
    }

    public function testColumnWithStringType()
    {
        $table = new SchemaTableBuilder('products', new SqlitePlatform());
        $table->column('id', 'INTEGER');
        $table->column('name', 'TEXT');
        $this->assertEquals(
            'CREATE TABLE products (id INTEGER, name TEXT)',
            $table->build()
        );
    }

    public function testAddColumnAfterBuild()
    {
        $table = new SchemaTableBuilder('logs', new SqlitePlatform());
        $table->column('id', 'INTEGER');
        $this->assertEquals(
            'CREATE TABLE logs (id INTEGER)',
            $table->build()
        );
        $table->addColumn('msg', 'TEXT');
        $this->assertEquals(
            'CREATE TABLE logs (id INTEGER, msg TEXT)',
            $table->build()
        );
    }
}
