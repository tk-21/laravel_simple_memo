<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Memo;
use App\Models\Tag;
use App\Models\MemoTag;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $tags = Tag::where('user_id', '=', \Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('id', 'DESC')
            ->get();

        return view('create', compact('tags')); //viewに渡す
    }

    public function store(Request $request)
    {
        $posts = $request->all();

        //ここからトランザクション開始
        DB::transaction(function () use ($posts) { //クロージャー（独立した空間）
            //メモIDをインサートして取得
            $memo_id = Memo::insertGetId(['content' => $posts['content'], 'user_id' => \Auth::id()]); //新しくインサートしたメモのIDを返す
            $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])->exists(); //user_idとnameが同じであれば既に存在すると判断できる
            //新規タグが入力されているかチェック
            //新規タグが既にtagsテーブルに存在するのかチェック（タグの存在チェックをしないと同じ名前のタグが複数存在してしまう）
            if ((!empty($posts['new_tag']) || $posts['new_tag'] === "0") && !$tag_exists) {
                //新規タグが既に存在しなければ、tagsテーブルにインサートし、IDを取得
                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
                //memo_tagsにインサートして、メモとタグを紐付ける
                MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag_id]);
            }
            //既存タグが紐付けられた場合→memo_tagsにインサート
            if (!empty($posts['tags'][0])) {
                foreach ($posts['tags'] as $tag) {
                    MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag]);
                }
            }
        });
        //ここまでがトランザクションの範囲

        //成功すればhomeにリダイレクト
        return redirect(route('home'));
    }

    public function edit($id) //URLのパラメータを引数として取得
    {
        $edit_memo = Memo::select('memos.*', 'tags.id AS tag_id')
            ->leftJoin('memo_tags', 'memo_tags.memo_id', '=', 'memos.id')
            ->leftJoin('tags', 'memo_tags.tag_id', '=', 'tags.id')
            ->where('memos.user_id', '=', \Auth::id()) //自分のメモである（ログインしているユーザーによって動的に変わるようにする）
            ->where('memos.id', '=', $id)
            ->whereNull('memos.deleted_at')
            ->get(); //複数行とってきたい場合はget

        $include_tags = [];
        foreach ($edit_memo as $memo) {
            array_push($include_tags, $memo['tag_id']);
        }

        //タグ一覧を取ってくる
        $tags = Tag::where('user_id', '=', \Auth::id())
        ->whereNull('deleted_at')
        ->orderBy('id', 'DESC')
        ->get();

        return view('edit', compact('edit_memo', 'include_tags', 'tags')); //取ってきたメモをviewに渡す
    }

    public function update(Request $request)
    {
        $posts = $request->all();

        DB::transaction(function () use($posts) {
            Memo::where('id', $posts['memo_id'])->update(['content' => $posts['content']]); //updateをする際は必ずwhereで行を指定するような情報を入れる。そのためにtype hiddenで埋め込んだ
            //一旦メモとタグの紐付けを削除
            MemoTag::where('memo_id', '=', $posts['memo_id'])->delete();
            //再度メモとタグの紐付け
            foreach($posts['tags'] as $tag) {
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag]);
            }
            //もし、新しいタグの入力があれば、インサートして紐付ける
            //自分のユーザーIDとタグテーブルのユーザーIDが一致するところかつ、投げられてきたnew_tagと一致するところがあれば、存在する
            $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])->exists();//存在すればtrueを返す
            //新規タグが入力されているかチェック
            //新規タグが既にtagsテーブルに存在するのかチェック（タグの存在チェックをしないと同じ名前のタグが複数存在してしまう）
            if ((!empty($posts['new_tag']) || $posts['new_tag'] === "0") && !$tag_exists) {
                //新規タグが既に存在しなければ、tagsテーブルにインサートし、IDを取得
                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
                //memo_tagsにインサートして、メモとタグを紐付ける
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag_id]);
            }

        });


        return redirect(route('home'));
    }

    public function destroy(Request $request)
    {
        $posts = $request->all();

        Memo::where('id', $posts['memo_id'])->update(['deleted_at' => date("Y-m-d H:i:s", time())]); //deleted_atに削除された時間を追加することで論理削除を行う

        return redirect(route('home'));
    }
}
