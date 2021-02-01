<?php
/**
 * 评论服务层
 *
 * Created By 皮神
 * Date: 2021/1/11
 */
declare(strict_types=1);

namespace App\Services;

use App\Enums\Comment\CollectType;
use App\Models\Comment;
use App\Services\User\UserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class CommentService extends BaseService
{
    /**
     * @param  int  $goodsId
     * @param  int  $page
     * @param  int  $limit
     * @return LengthAwarePaginator
     */
    public function getByGoodsId(
        int $goodsId,
        int $page = 1,
        int $limit = 2,
        string $sort = 'add_time',
        string $order = 'desc'
    ): LengthAwarePaginator {
        return Comment::query()
            ->where('value_id', $goodsId)
            ->where('type', CollectType::Goods)
            ->orderBy($sort, $order)
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function getWithUserInfo(int $goodsId, int $page = 1, int $limit = 2)
    {
        $comments = $this->getByGoodsId($goodsId, $page, $limit);
        $userIds = array_unique(Arr::pluck($comments->items(), 'user_id'));
        $users = UserService::getInstance()->getByIds($userIds)->keyBy('id');

        $data = collect($comments->items())->map(function (Comment $comment) use ($users) {
            $user = $users->get($comment->user_id);
            $comment = $comment->toArray();
            $comment['picList'] = $comment['picUrls'];
            $comment = Arr::only($comment, ['id', 'addTime', 'content', 'adminContent', 'picList']);
            $comment['nickname'] = $user->nickname ?? '';
            $comment['avatar'] = $user->avatar ?? '';

            return $comment;
        });

        return [
            'count' => $comments->total(),
            'data' => $data
        ];
    }
}
