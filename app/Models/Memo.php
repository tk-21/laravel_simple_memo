<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memo extends Model
{
    use HasFactory;

    public function getMyMemo()
    {
        $query_tag = \Request::query('tag'); //Requestファザードを使う。バックスラッシュをつけるとインポートしていなくても使えるようになる

        //queryメソッドを使うことによってクエリビルダを分割することができる
        $query = Memo::query()->select('memos.*')
            ->where('user_id', '=', \Auth::id()) //ログインしているユーザーによって動的に変わるようにする
            ->whereNull('deleted_at') //deleted_atに値があるものは削除されたとみなす
            ->orderBy('updated_at', 'DESC');

            //もしクエリパラメータtagがあればタグで絞り込み
        if (!empty($query_tag)) {
            $query->leftJoin('memo_tags', 'memo_tags.memo_id', '=', 'memos.id') //結合してから絞り込む
                ->where('memo_tags.tag_id', '=', $query_tag);
        }

        $memos = $query->get();

        return $memos;
    }
}
