<?php
use PHPUnit\Framework\TestCase;
use DBAL\Schema\SchemaTableBuilder;

class SchemaTableBuilderTest extends TestCase
{
    public function testLambdaColumnDefinition()
    {
        $table = new SchemaTableBuilder('users');
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
        $table = new SchemaTableBuilder('items');
        $table->addColumn('id', function ($c) {
            $c->integer()->primaryKey();
        });
        $table->addColumn('name', 'TEXT');
        $this->assertEquals(
            'CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT)',
            $table->build()
        );
    }
}
