<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Memo;

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
            //必要な条件で絞ってメモを取得
            $memos = Memo::select('memos.*')
                ->where('user_id', '=', \Auth::id()) //ログインしているユーザーによって動的に変わるようにする
                ->whereNull('deleted_at') //deleted_atに値があるものは削除されたとみなす
                ->orderBy('updated_at', 'DESC')
                ->get();

                $view->with('memos', $memos);
        });
    }
}
