# Core Architecture

DBAL revolves around a query builder and a set of nodes that generate SQL
fragments. The main class is `Crud`, which extends `Query` to fluently compose
`SELECT`, `INSERT`, `UPDATE` and `DELETE`. The goal is to keep a thin layer over
PDO with no extra dependencies while providing an extensible API.

## Base node classes

Nodes implement `NodeInterface` and usually extend `Node`, which manages the
tree of child nodes. Each node outputs part of the statement through `send()`
and can contain other nodes.

`QueryNode` is the root of every query and adds children for tables, fields,
filters and other elements. Depending on the message type only the necessary
nodes are used to generate the SQL.

`FilterNode` lets you register operators via `FilterNode::filter()`. Filters
receive the field name, the supplied value and the `Message` object where the
SQL fragment should be inserted. The file defines basic operators such as `eq`,
`ne`, `gt` or `like`.

## Crud and ResultIterator

`Crud` adds middlewares, mappers and relation handling on top of the query
builder. Calling `select()` returns a `ResultIterator` that applies the mappers
and executes the middlewares. This iterator loads relations lazily through
`LazyRelation`.

## Philosophy

As mentioned in the introduction, DBAL aims to be a lightweight and agnostic
layer offering a fluent builder, dynamic filters and iteration utilities without
coupling to any framework.

## Extending functionality

The node and filter architecture is designed to grow without touching the core:

- **Custom filters**: `FilterNode::filter()` lets you add new operators and use
  them from conditions or dynamic filters.
- **New nodes**: To support SQL structures not covered you can create a class
  implementing `NodeInterface` (or extending `Node`/`NotImplementedNode`) and
  add it to the `QueryNode` tree or to custom queries. `CaseNode` and
  `SubQueryNode` encapsulate more advanced logic.

When creating a new node you can also modify `QueryNode::build()` so it includes
the node during construction or invoke it manually from a `Query` instance.

