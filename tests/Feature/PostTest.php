<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(User $user): Post
    {
        $category = Category::create(['name' => '雑記']);

        return Post::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => '元のタイトル',
            'content' => '元の本文',
        ]);
    }

    public static function validContents(): array
    {
        return [
            '139 Japanese characters' => [str_repeat('あ', 139)],
            '140 Japanese characters' => [str_repeat('あ', 140)],
            '140 characters including emoji' => [str_repeat('あ', 139).'😀'],
            '140 characters including newline' => [str_repeat('あ', 138)."\nい"],
        ];
    }

    #[DataProvider('validContents')]
    public function test_owner_can_update_content_within_limit(string $content): void
    {
        $user = User::factory()->create();
        $post = $this->createPost($user);

        $this->actingAs($user)->put(route('posts.update', $post), [
            'title' => '更新したタイトル',
            'category_id' => $post->category_id,
            'content' => $content,
        ])->assertRedirect(route('posts.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => '更新したタイトル',
            'content' => $content,
        ]);
    }

    public static function invalidContents(): array
    {
        return [
            '141 Japanese characters' => [str_repeat('あ', 141)],
            '141 characters including emoji' => [str_repeat('あ', 140).'😀'],
            'combined emoji counts as multiple characters' => [str_repeat('あ', 139).'👨‍👩‍👧‍👦'],
        ];
    }

    #[DataProvider('invalidContents')]
    public function test_over_limit_content_returns_japanese_error_without_saving(string $content): void
    {
        $user = User::factory()->create();
        $post = $this->createPost($user);
        $original = $post->fresh()->getAttributes();
        $category = Category::create(['name' => '別のトピック']);
        $input = [
            'title' => '更新したタイトル',
            'category_id' => $category->id,
            'content' => $content,
        ];

        $this->actingAs($user)->from(route('posts.edit', $post))
            ->put(route('posts.update', $post), $input)
            ->assertRedirect(route('posts.edit', $post))
            ->assertSessionHasErrors(['content' => '本文は140文字以内で入力してください。'])
            ->assertSessionHasInput('content', $content)
            ->assertSessionHasInput('title', $input['title'])
            ->assertSessionHasInput('category_id', $category->id);

        $this->assertSame($original, $post->fresh()->getAttributes());
    }

    public function test_empty_content_returns_japanese_error(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost($user);

        $this->actingAs($user)->put(route('posts.update', $post), [
            'title' => $post->title,
            'category_id' => $post->category_id,
            'content' => '',
        ])->assertSessionHasErrors(['content' => '本文を入力してください。']);

        $this->assertSame('元の本文', $post->fresh()->content);
    }

    public function test_browser_form_line_endings_count_as_one_character(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost($user);
        $content = str_repeat('あ', 138)."\r\nい";

        $this->actingAs($user)->put(route('posts.update', $post), [
            'title' => $post->title,
            'category_id' => $post->category_id,
            'content' => $content,
        ])->assertRedirect(route('posts.index'))->assertSessionHasNoErrors();

        $this->assertSame(str_repeat('あ', 138)."\nい", $post->fresh()->content);
    }

    public function test_edit_screen_shows_remaining_count_for_existing_content(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost($user);
        $post->update(['content' => str_repeat('あ', 139).'😀']);

        $this->actingAs($user)->get(route('posts.edit', $post))
            ->assertOk()
            ->assertSee('aria-describedby="content-count"', false)
            ->assertSee('残り0字');
    }

    public function test_edit_screen_shows_retained_content_error_and_over_limit_count(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost($user);
        $content = str_repeat('あ', 140).'😀';

        $this->actingAs($user)->from(route('posts.edit', $post))
            ->put(route('posts.update', $post), [
                'title' => $post->title,
                'category_id' => $post->category_id,
                'content' => $content,
            ])->assertRedirect(route('posts.edit', $post));

        $this->get(route('posts.edit', $post))
            ->assertOk()
            ->assertSee($content)
            ->assertSee('本文は140文字以内で入力してください。')
            ->assertSee('1字超過')
            ->assertSee('content-count is-over-limit');
    }

    public function test_other_user_cannot_edit_or_update_post(): void
    {
        $post = $this->createPost(User::factory()->create());
        $this->actingAs(User::factory()->create());

        $this->get(route('posts.edit', $post))->assertForbidden();
        $this->put(route('posts.update', $post), [
            'title' => '変更',
            'category_id' => $post->category_id,
            'content' => '変更',
        ])->assertForbidden();

        $this->assertSame('元の本文', $post->fresh()->content);
    }

    public function test_timeline_still_shows_posts_and_owner_can_delete(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost($user);

        $this->actingAs($user)->get(route('posts.index'))
            ->assertOk()->assertSee('元の本文');
        $this->delete(route('posts.destroy', $post))
            ->assertRedirect(route('posts.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
