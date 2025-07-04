# Core Architecture

DBAL se estructura alrededor de un constructor de consultas y un conjunto de nodos que generan fragmentos de SQL. La clase principal es `Crud`, que hereda de `Query` para componer `SELECT`, `INSERT`, `UPDATE` y `DELETE` de forma fluida. El objetivo es mantener la capa delgada sobre PDO sin dependencias adicionales y ofrecer un API extensible.

## Clases base de nodos

Los nodos implementan `NodeInterface` y habitualmente extienden `Node`, que gestiona el árbol de subnodos. Cada nodo produce parte de la sentencia mediante `send()` y puede contener otros nodos.

`QueryNode` es la raíz de todas las consultas y agrega subnodos para tablas, campos, filtros y demás elementos. Dependiendo del tipo de mensaje se emplean solo los nodos necesarios para generar la SQL.

`FilterNode` permite registrar operadores mediante `FilterNode::filter()`. Los filtros reciben el nombre del campo, el valor proporcionado y el objeto `Message` donde deben insertar la porción de SQL. El archivo define operadores básicos como `eq`, `ne`, `gt` o `like`.

## Crud y ResultIterator

`Crud` añade middlewares, mapeadores y gestión de relaciones sobre el constructor de consultas. Al ejecutar `select()` se devuelve un `ResultIterator` que aplica los mapeadores y ejecuta los middlewares. Este iterador carga relaciones de forma perezosa mediante `LazyRelation`.

## Filosofía

Tal como se menciona en la introducción de la documentación, DBAL pretende ser una capa liviana y agnóstica que ofrezca un constructor fluido, filtros dinámicos y utilidades de iteración sin acoplarse a ningún framework concreto.

## Extensión de funcionalidades

La arquitectura por nodos y filtros está pensada para ampliarse sin tocar el núcleo:

- **Filtros personalizados**: `FilterNode::filter()` permite añadir nuevos operadores y utilizarlos desde las condiciones o desde los filtros dinámicos.
- **Nuevos nodos**: Para soportar estructuras SQL no contempladas se puede crear una clase que implemente `NodeInterface` (o extienda `Node`/`NotImplementedNode`) y añadirla al árbol de `QueryNode` o a consultas personalizadas. `CaseNode` y `SubQueryNode` son ejemplos de nodos que encapsulan lógica más avanzada.

Al crear un nodo nuevo también puede modificarse `QueryNode::build()` para que incluya el nodo durante la construcción o invocarlo manualmente desde una instancia de `Query`.

