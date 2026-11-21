<?php
// Throwaway test. Delete this file afterwards.
// Uses a fake "public" disk and a rolled-back transaction: no real files or rows are kept.

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Services\BlogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function check(string $label, bool $ok): void
{
    echo ($ok ? 'PASS' : 'FAIL') . '  ' . $label . PHP_EOL;
}

$disk = Storage::fake('public');
$svc = new BlogService();
$img = fn(string $name = 'a.jpg') => UploadedFile::fake()->create($name, 20, 'image/jpeg');

DB::beginTransaction();
try {
    $c = BlogCategory::create(['name' => 'Scratch ' . uniqid()]);
    $data = fn(array $o = []) => array_merge([
        'title' => 'T',
        'slug' => 'scratch-' . uniqid(),
        'excerpt' => 'e',
        'content' => 'c',
        'category_id' => $c->id,
        'status' => 'draft',
        'author_name' => null,
        'published_at' => null,
        'is_featured' => false,
    ], $o);

    // create
    $a = $svc->create($data(['status' => 'published']), $img());
    check('create: image stored under blogs/', str_starts_with((string) $a->featured_image, 'blogs/'));
    check('create: file exists on disk', $disk->exists($a->featured_image));
    check('create: published without a date gets published_at', $a->published_at !== null);
    check('create: category is loaded', $a->relationLoaded('category'));

    $d = $svc->create($data());
    check('create: draft without image has a null image', $d->featured_image === null);
    check('create: draft keeps published_at null', $d->published_at === null);

    $e = $svc->create($data(['status' => 'published', 'published_at' => '2027-01-01 10:00:00']));
    check('create: an explicit published_at is kept', $e->published_at->format('Y-m-d H:i') === '2027-01-01 10:00');

    // update
    $old = $a->featured_image;
    $a = $svc->update($a, $data(['slug' => $a->slug, 'status' => 'published']), $img('b.jpg'));
    check('update: old file deleted after replace', ! $disk->exists($old));
    check('update: new file exists', $a->featured_image !== $old && $disk->exists($a->featured_image));

    $keep = $a->featured_image;
    $a = $svc->update($a, $data(['slug' => $a->slug, 'title' => 'Changed']));
    check('update: image untouched when none is sent', $a->featured_image === $keep && $disk->exists($keep));
    check('update: fields are changed', $a->title === 'Changed');

    $a = $svc->update($a, $data(['slug' => $a->slug]), null, true);
    check('update: remove flag clears the path', $a->featured_image === null);
    check('update: remove flag deletes the file', ! $disk->exists($keep));

    $a = $svc->update($a, $data(['slug' => $a->slug]), $img('c.jpg'), true);
    check('update: a new upload wins over the remove flag', $a->featured_image !== null && $disk->exists($a->featured_image));

    // failures leave no mess
    $before = count($disk->allFiles('blogs'));
    try {
        $svc->create($data(['slug' => $a->slug]), $img('d.jpg'));
        check('create: a duplicate slug must fail', false);
    } catch (Throwable $ex) {
        check('create: a duplicate slug fails', true);
    }
    check('create: a failed database write leaves no orphan file', count($disk->allFiles('blogs')) === $before);

    $x = $svc->create($data(), $img('x.jpg'));
    $xOld = $x->featured_image;
    $before = count($disk->allFiles('blogs'));
    try {
        $svc->update($x, $data(['slug' => $a->slug]), $img('y.jpg'));
        check('update: a duplicate slug must fail', false);
    } catch (Throwable $ex) {
        check('update: a duplicate slug fails', true);
    }
    check('update: a failed write keeps the old file', $disk->exists($xOld));
    check('update: a failed write leaves no orphan file', count($disk->allFiles('blogs')) === $before);

    // delete
    $x = Blog::find($x->id);
    $svc->delete($x);
    check('delete: row removed', Blog::find($x->id) === null);
    check('delete: file removed', ! $disk->exists($xOld));

    $s1 = $svc->create($data(), $img('s.jpg'));
    $s2 = Blog::create($data(['featured_image' => $s1->featured_image]));
    $svc->delete($s1);
    check('delete: a shared file is kept while another post uses it', $disk->exists($s2->featured_image));
    $svc->delete($s2);
    check('delete: the shared file goes with the last post', ! $disk->exists($s1->featured_image));

    $disk->put('other/secret.txt', 'x');
    $o = Blog::create($data(['featured_image' => 'other/secret.txt']));
    $svc->delete($o);
    check('delete: a path outside blogs/ is never deleted', $disk->exists('other/secret.txt'));
    $t = Blog::create($data(['featured_image' => 'blogs/../other/secret.txt']));
    $svc->delete($t);
    check('delete: a path with ".." is never deleted', $disk->exists('other/secret.txt'));

    // publish / unpublish
    $p = $svc->publish($svc->create($data()));
    check('publish: status is published', $p->status === 'published');
    check('publish: published_at set when empty', $p->published_at !== null);
    $stamp = $p->published_at->toDateTimeString();
    $p = $svc->unpublish($p);
    check('unpublish: status is draft', $p->status === 'draft');
    check('unpublish: published_at is kept', $p->published_at->toDateTimeString() === $stamp);
    $p = $svc->publish($p);
    check('publish again: the original published_at is kept', $p->published_at->toDateTimeString() === $stamp);

    $f = $svc->publish($svc->create($data(['published_at' => '2027-06-01 09:00:00'])));
    check('publish: a scheduled (future) date is kept', $f->published_at->format('Y-m-d H:i') === '2027-06-01 09:00');
    check('published() scope hides a scheduled post', ! Blog::published()->where('id', $f->id)->exists());
    check('published() scope shows a live post', Blog::published()->where('id', $p->id)->exists());
} finally {
    DB::rollBack();
    $disk->deleteDirectory('blogs');
    $disk->deleteDirectory('other');
    echo 'rolled back, nothing saved' . PHP_EOL;
}
