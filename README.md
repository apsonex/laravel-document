# Laravel Document

## Pending Document

Create pending document object

```php
use Apsonex\LaravelDocument\Support\PendingDocument\PendingDocument;

(new PendingDocument)
    ->imageSource(UploadedFile|RetsObject|string)
    ->parentModel(Model)
    ->withoutOriginal()
    ->setVariations($variations = [])
    ->targetPath('full/image/path.jpg')
    ->setAddedBy(auth()->id())
    ->visibilityPublic()
    ->disk(\Illuminate\Filesystem\Filesystem)

```

## Image Document

Create image document from pending document

```php
use Apsonex\LaravelDocument\Support\PendingDocument\PendingDocument;
$pendingObject = new PendingDocument();

/** @var \Apsonex\LaravelDocument\Models\Document $document */
$document = \Apsonex\LaravelDocument\Facades\Document::persist($pendingObject)
```