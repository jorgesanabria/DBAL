# LazyRelation

`LazyRelation` is a small helper returned when related rows are not eagerly loaded with `with()`. It delays the underlying query until the relation value is actually used.

## Automatic loading

A `LazyRelation` instance receives a loader callback. The callback is executed only once, the first time the relation is accessed. Subsequent calls return the previously loaded data.

```php
$book = iterator_to_array($books->where(['id' => 1])->select())[0];
// relation not fetched yet
$title = $book['title'];
$author = $book['author'];        // triggers the query behind the scenes
```

## Iterability

`LazyRelation` implements `IteratorAggregate`. When used in a `foreach` loop it automatically loads the related rows and yields them one by one.

```php
$book = iterator_to_array($books->where(['id' => 1])->select())[0];
foreach ($book['reviews'] as $review) {   // loader runs on first iteration
    echo $review['text'];
}
```

## JSON serialisation

The object also implements `JsonSerializable`. Calling `json_encode()` on an array or object containing `LazyRelation` instances causes each relation to be loaded and converted to plain data.

```php
$book = iterator_to_array($books->where(['id' => 1])->select())[0];
echo json_encode($book);  // author and reviews are included
```

`LazyRelation` can be manually invoked as `$book['author']()` or `$book['author']->get()` if needed but is usually transparent when accessed, iterated or encoded.
