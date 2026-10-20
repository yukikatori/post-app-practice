<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user', 'category'])->latest()->get();

        return view('posts.index', compact('posts'));
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);

        $categories = Category::orderBy('id')->get();

        return view('posts.edit', compact('post', 'categories'));
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        if (is_string($request->input('content'))) {
            $request->merge([
                'content' => str_replace(["\r\n", "\r"], "\n", $request->input('content')),
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:140',
            'category_id' => 'required|exists:categories,id',
        ], [
            'title.required' => 'タイトルを入力してください。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'content.required' => '本文を入力してください。',
            'content.string' => '本文は文字列で入力してください。',
            'content.max' => '本文は140文字以内で入力してください。',
            'category_id.required' => 'トピックを選択してください。',
            'category_id.exists' => '選択されたトピックは無効です。',
        ]);

        $post->update($validated);

        return redirect()->route('posts.index');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('posts.index');
    }
}
