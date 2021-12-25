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
            //概要はviewcomposerに記述して詳細はMemoモデルに任せる
            //インスタンス化
            $memo_model = new Memo();
            $memos = $memo_model->getMyMemo();


            $tags = Tag::where('user_id', '=', \Auth::id())
                ->whereNull('deleted_at')
                ->orderBy('id', 'DESC')
                ->get();

            $view->with('memos', $memos)->with('tags', $tags);
        });
    }
}
