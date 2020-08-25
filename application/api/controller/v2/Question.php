<?php

namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use think\console\Input;

/**
 * 答题接口
 */
class Question extends ApiCommon
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';
    protected $model = null;

    protected function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Question();
    }

    /**
     * 题目详情
     * @param int $id 题目ID
     *
     */
    public function index()
    {
        $id = intval(input("id"));
        if (!$id) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }
        $questionInfo = $this->model->get($id);
        if (!$questionInfo) {
            $lang = lang("not_question");
            err(200, "not_question", $lang['code'], $lang['message']);
        }

        $data = [
            'id' => $questionInfo['id'],
            'cid' => $questionInfo['cid'],
            'question' => $questionInfo['question'],
            'options' => [
                ['A', $questionInfo['optionA']],
                ['B', $questionInfo['optionB']],
                ['C', $questionInfo['optionC']],
                ['D', $questionInfo['optionD']],
            ],
            'answer' => $questionInfo['answer'],
            'tips' => $questionInfo['tips'],
            'analyze' => $questionInfo['analyze'],
        ];

        ok($data);
    }


    /**
     * 题目列表
     * @param int $page 页码
     * @param int $pagesize 每页数，题目数量，默认随机生成10个
     *
     */

    public function questionList()
    {
        $cid = intval(input("cid"));
        $pagesize = intval(input("pagesize", 10));
        $page = intval(input("page", 1));
        $rand = 'rand()';
        $where = [];
        if ($cid) {
            $where['cid'] = $cid;
        }
        $questionList = \app\common\model\Question::where($where)->page($page)->orderRaw($rand)->paginate($pagesize);
        $list = [];

        foreach ($questionList as $k => $v) {
            $list[$k] = [
                'id' => $v['id'],
                'cid' => $v['cid'],
                'question' => $v['question'],
                'question_category' => \app\common\model\QuestionCategory::get(['id' => $v['cid']])->value('name'),
                'options' => [
                    ['A', $v['optionA']],
                    ['B', $v['optionB']],
                    ['C', $v['optionC']],
                    ['D', $v['optionD']],
                ],
                'answer' => $v['answer'],
                'tips' => $v['tips'],
                'analyze' => $v['analyze'],
            ];
        }
        ok([
            "items" => $list,
            "total" => $pagesize
        ]);
    }


    /**
     * 提交答案接口
     * @param string $post_result 提交结果，以字符串提交，格式“题目ID-答题答案,"，例如"1-A,2-B,3-C,4-D,5-B"
     * @param string $token 用户TOKEN（必填）
     * @param string $tv_no 机顶盒盒号（非必填）
     * @param string $tv_card 机顶盒卡号（非必填）
     *
     */
    public function answer()
    {
        $post_result = trim(input('post_result'));
        $tv_no = trim(input('tv_no'));
        $tv_card = trim(input('tv_card'));
        $duration = trim(input('duration'));

        $ids = [];
        $options = [];

        if (!$post_result) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }
        if (!empty($post_result)) {
            $newoptionsarr = explode(',', $post_result);
            foreach ($newoptionsarr as $value) {
                $ne = explode('-', $value);
                if ($ne) {
                    $ids[] = $ne[0];
                    $options[] = $ne[1];
                }
            }
        }

        $correctsum = 0;
        for ($id = 0; $id < count($ids); $id++) {
            $questionInfo = $this->model->get($ids[$id]);
            if (!$questionInfo) {
                $lang = lang("not_question");
                err(200, "not_question", $lang['code'], $lang['message']);
            }

            if ($questionInfo['answer'] == $options[$id]) {
                $correctsum += 1;//正确个数
            }

        }

        $averge = $correctsum / count($ids);//正确概率

        \app\common\model\QuestionLog::create([
            'uid' => $this->uid,
            'tv_no' => $tv_no,
            'tv_card' => $tv_card,
            'qids' => json_encode($ids),
            'answers' => json_encode($options),
            'question_count' => count($ids),
            'scores' => $averge * 100,
            'correctsum' => $correctsum,
            'duration' => $duration,
        ]);


        ok([
            'score' => $averge * 100,
            'correctsum' => $correctsum,
            'totalsum' => count($ids),
            'duration' => $duration,
        ]);
    }


    /**
     * 获取个人答题信息接口
     * @param string $token 用户TOKEN（必填）
     * @ApiReturnParams   (name="total_correct_sum", type="integer", required=true, description="答题总正确条数")
     * @ApiReturnParams   (name="total_sum", type="integer", required=true, description="答题总条数")
     * @ApiReturnParams   (name="total_scores", type="integer", required=true, description="答题总分数")
     * @ApiReturnParams   (name="answer_count", type="integer", required=true, description="答题总次数")
     * @ApiReturnParams   (name="averge", type="double", required=true, description="答题正确率")
     * @ApiReturn   ({
    "status": 200,
    "exception": "",
    "code": 0,
    "message": "",
    "data": {
    "total_correct_sum": 10,
    "total_sum": 6,
    "total_scores": 120,
    "answer_count": 2,
    "averge": 0.6
    }
    })
     */
    public function userInfo()
    {
        $list = \app\common\model\QuestionLog::where(['uid' => $this->uid])->select();
        $count = \app\common\model\QuestionLog::where(['uid' => $this->uid])->count();
        $total_correct_sum = 0;//总答对个数
        $total_sum = 0;//总答题数
        $total_scores = 0;//总积分
        foreach ($list as $k => $v) {
            $total_correct_sum += $v['correctsum'];
            $total_sum += $v['question_count'];
            $total_scores += $v['scores'];
        }

        ok([
            'total_correct_sum' => $total_sum,
            'total_sum' => $total_correct_sum,
            'total_scores' => $total_scores,
            'answer_count' => $count,
            'averge' => $total_sum == 0 ? 0 : ($total_correct_sum / $total_sum),
        ]);
    }

    /**
     * 获取个人答题记录接口
     * @param string $token 用户TOKEN（必填）
     * @param string $page 页数page
     * @param string $pagesize 条数pagesize
     * @param string $orders 排序orders
     * @ApiReturnParams   (name="id", type="integer", required=true, description="题目id")
     * @ApiReturnParams   (name="question", type="integer", required=true, description="题目标题")
     * @ApiReturnParams   (name="add_time", type="string", required=true, description="答题时间")
     * @ApiReturnParams   (name="correctsum", type="integer", required=true, description="答对题数")
     * @ApiReturnParams   (name="question_count", type="integer", required=true, description="答题数量")
     * @ApiReturnParams   (name="averge", type="double", required=true, description="答题概率")
     * @ApiReturnParams   (name="duration", type="integer", required=true, description="答题用时")
     * @ApiReturn ({
    "status": 200,
    "exception": "",
    "code": 0,
    "message": "",
    "data": {
    "items": [
    {
    "id": 43,
    "question": "旅行社应当提示参加团队旅游的旅游者按照规定购买____保险。",
    "add_time": "2019-12-18 15:54:15",
    "correctsum": 3,
    "question_count": 5,
    "averge": 0.6,
    "duration": 5
    },
    {
    "id": 43,
    "question": "旅行社应当提示参加团队旅游的旅游者按照规定购买____保险。",
    "add_time": "2019-12-18 15:54:15",
    "correctsum": 3,
    "question_count": 5,
    "averge": 0.6,
    "duration": 5
    }
    ],
    "pagesize": 10,
    "curpage": 1,
    "totalpage": 1,
    "total": 2
    }
    })
     */
    public function myAnswerLog()
    {
        $page = intval(input("page", 1));
        $pagesize = intval(input("pagesize", 10));
        $orders = trim(input("orders", "add_time desc"));

        $where = [];
        if ($this->uid) {
            $where['uid'] = $this->uid;
        }

        $questionLogList = \app\common\model\QuestionLog::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\QuestionLog::where($where)->count();

        foreach ($questionLogList as $k => $v) {
            foreach (json_decode($v['qids']) as $key => $value) {
                $questionInfo = $this->model->get($value);
//                if (!$questionInfo) {
//                    $lang = lang("not_question");
//                    err(200, "not_question", $lang['code'], $lang['message']);
//                }

                $questionLogList[$k] = [
                    'id' => $questionInfo['id'],
                    'add_time' => format_time($v['add_time']),
                    'correctsum' => $v['correctsum'],
                    'question_count' => $v['question_count'],
                    'averge' => $v['correctsum'] / $v['question_count'],
                    'duration' => $v['duration'],
                ];
            }

        }

        ok([
            "items" => $questionLogList,
            "pagesize" => $pagesize,
            "curpage" => $page,
            "totalpage" => ceil($total / $pagesize),
            "total" => $total
        ]);
    }


}
