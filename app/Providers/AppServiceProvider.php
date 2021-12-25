<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Memo;
use App\Models\Tag;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //全てのメソッドが呼ばれる前に先に呼ばれるメソッド
        view()->composer('*', function ($view) {
            $query_tag = \Request::query('tag');//Requestファザードを使う。バックスラッシュをつけるとインポートしていなくても使えるようになる

            if(!empty($query_tag)) {
                $memos = Memo::select('memos.*')
                ->leftJoin('memo_tags', 'memo_tags.memo_id', '=', 'memos.id')//結合してから絞り込む
                ->where('memo_tags.tag_id', '=', $query_tag)
                ->where('user_id', '=', \Auth::id()) //ログインしているユーザーによって動的に変わるようにする
                ->whereNull('deleted_at') //deleted_atに値があるものは削除されたとみなす
                ->orderBy('updated_at', 'DESC')
                ->get();
            } else {
                //必要な条件で絞ってメモを取得
                $memos = Memo::select('memos.*')
                    ->where('user_id', '=', \Auth::id()) //ログインしているユーザーによって動的に変わるようにする
                    ->whereNull('deleted_at') //deleted_atに値があるものは削除されたとみなす
                    ->orderBy('updated_at', 'DESC')
                    ->get();
            }

            $tags = Tag::where('user_id', '=', \Auth::id())
                ->whereNull('deleted_at')
                ->orderBy('id', 'DESC')
                ->get();

            $view->with('memos', $memos)->with('tags', $tags);
        });
    }
}
