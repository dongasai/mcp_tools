<?php

namespace App\UserAdmin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Form;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;
use App\Modules\Task\Models\Task;
use App\Modules\Task\Models\TaskComment;
use App\Modules\Project\Models\Project;
use App\Modules\User\Models\User;
use App\Modules\Task\Services\TaskCommentService;
use App\Modules\Task\Enums\COMMENTTYPE;
use Illuminate\Http\Request;

class TaskController extends AdminController
{
    protected $title = '任务管理';

    public function index(Content $content)
    {
        return $content
            ->title($this->title)
            ->description('管理您的任务')
            ->body($this->grid());
    }

    protected function grid()
    {
        $grid = new Grid(new Task());

        // 只显示当前用户的任务
        $user = $this->getCurrentUser();
        if ($user) {
            // 通过项目关联限制只显示用户自己的任务
            $userProjectIds = Project::where('user_id', $user->id)->pluck('id');
            $grid->model()->whereIn('project_id', $userProjectIds);
        } else {
            // 如果无法获取用户，不显示任何任务
            $grid->model()->where('id', -1);
        }

        $grid->column('id', 'ID')->sortable();
        $grid->column('title', '任务标题')->limit(40);
        $grid->column('project.name', '所属项目')->limit(20);

        $grid->column('type', '任务类型')->using([
            'main' => '主任务',
            'sub' => '子任务'
        ])->label([
            'main' => 'primary',
            'sub' => 'info'
        ]);

        $grid->column('status', '状态')->using([
            'pending' => '待处理',
            'in_progress' => '进行中',
            'completed' => '已完成',
            'cancelled' => '已取消'
        ])->label([
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger'
        ]);

        $grid->column('priority', '优先级')->using([
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'urgent' => '紧急'
        ])->label([
            'low' => 'default',
            'medium' => 'info',
            'high' => 'warning',
            'urgent' => 'danger'
        ]);

        $grid->column('assigned_to', '分配给')->display(function ($userId) {
            if ($userId) {
                $user = User::find($userId);
                return $user ? $user->name : '未知用户';
            }
            return '未分配';
        });

        $grid->column('due_date', '截止日期')->sortable();
        $grid->column('created_at', '创建时间')->sortable();

        // 进度显示
        $grid->column('progress', '进度')->progressBar();

        $grid->filter(function($filter) {
            $filter->like('title', '任务标题');
            $filter->equal('status', '状态')->select([
                'pending' => '待处理',
                'in_progress' => '进行中',
                'completed' => '已完成',
                'cancelled' => '已取消'
            ]);
            $filter->equal('priority', '优先级')->select([
                'low' => '低',
                'medium' => '中',
                'high' => '高',
                'urgent' => '紧急'
            ]);
            // 只显示当前用户的项目
            $user = $this->getCurrentUser();
            $userProjects = $user ?
                Project::where('user_id', $user->id)->pluck('name', 'id')->toArray() :
                [];
            $filter->equal('project_id', '项目')->select($userProjects);
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Task());

        $form->text('title', '任务标题')->required();
        $form->textarea('description', '任务描述');

        $user = $this->getCurrentUser();
        // 只显示当前用户的项目
        $userProjects = $user ?
            Project::where('user_id', $user->id)->pluck('name', 'id')->toArray() :
            [];

        $form->select('project_id', '所属项目')->options($userProjects)->required()
             ->help('只能选择您自己的项目');

        $form->select('type', '任务类型')->options([
            'main' => '主任务',
            'sub' => '子任务'
        ])->default('main');

        $form->select('status', '状态')->options([
            'pending' => '待处理',
            'in_progress' => '进行中',
            'completed' => '已完成',
            'cancelled' => '已取消'
        ])->default('pending');

        $form->select('priority', '优先级')->options([
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'urgent' => '紧急'
        ])->default('medium');

        $form->select('assigned_to', '分配给')->options(
            User::pluck('name', 'id')->toArray()
        );

        $form->date('due_date', '截止日期');
        $form->number('progress', '进度')->min(0)->max(100)->default(0);
        $form->textarea('metadata', '元数据')->help('JSON格式的任务元数据')->default('{}');

        // 保存时验证项目归属
        $form->saving(function (Form $form) {
            $user = auth('user-admin')->user();
            if (!$user) {
                throw new \Exception('无法获取当前用户信息');
            }

            // 验证项目是否属于当前用户
            if ($form->project_id) {
                $project = Project::find($form->project_id);
                if (!$project || $project->user_id !== $user->id) {
                    throw new \Exception('您没有权限访问该项目');
                }
            }

            // 设置创建者
            if (!$form->model()->id) {
                $form->created_by = $user->id;
            }
        });

        return $form;
    }

    protected function detail($id)
    {
        $task = Task::with(['comments.user', 'comments.agent'])->findOrFail($id);

        // 验证任务归属权限
        $user = $this->getCurrentUser();
        if ($user) {
            $userProjectIds = Project::where('user_id', $user->id)->pluck('id');
            if (!$userProjectIds->contains($task->project_id)) {
                abort(403, '您没有权限访问此任务');
            }
        }

        $show = new Show($task);

        $show->field('id', 'ID');
        $show->field('title', '任务标题');
        $show->field('description', '任务描述');
        $show->field('project.name', '所属项目');
        $show->field('type', '任务类型');
        $show->field('status', '状态');
        $show->field('priority', '优先级');
        $show->field('progress', '进度')->as(function ($progress) {
            return $progress . '%';
        });
        $show->field('assigned_to', '分配给')->as(function ($userId) {
            if ($userId) {
                $user = User::find($userId);
                return $user ? $user->name : '未知用户';
            }
            return '未分配';
        });
        $show->field('due_date', '截止日期');
        $show->field('created_at', '创建时间');
        $show->field('updated_at', '更新时间');

        // 添加评论部分
        $show->divider();

        // 获取模态框和JavaScript代码
        $modalHtml = $this->getCommentModalHtml();
        $jsCode = $this->getCommentJavaScript();

        $show->field('comments', '任务评论')->as(function ($comments) use ($modalHtml, $jsCode) {
            if (empty($comments) || (is_object($comments) && $comments->isEmpty())) {
                $html = '<div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> 暂无评论
                    <button type="button" class="btn btn-primary btn-sm float-right" onclick="showAddCommentModal()">
                        <i class="fa fa-plus"></i> 添加评论
                    </button>
                </div>';

                // 添加评论相关的JavaScript和模态框
                $html .= $modalHtml;
                $html .= $jsCode;

                return $html;
            }

            $html = '<div class="comments-section">';
            $html .= '<div class="mb-3">
                <button type="button" class="btn btn-primary btn-sm" onclick="showAddCommentModal()">
                    <i class="fa fa-plus"></i> 添加评论
                </button>
            </div>';

            $commentsCollection = is_array($comments) ? collect($comments) : $comments;
            foreach ($commentsCollection->sortBy('created_at') as $comment) {
                $authorName = $comment->author_name;
                $authorType = $comment->author_type;
                $typeLabel = $comment->comment_type ? $comment->comment_type->label() : '一般';
                $isInternal = $comment->is_internal ? '<span class="badge badge-warning">内部</span>' : '';
                $isSystem = $comment->is_system ? '<span class="badge badge-info">系统</span>' : '';
                $edited = $comment->isEdited() ? '<small class="text-muted">(已编辑)</small>' : '';

                $html .= '<div class="card mb-3 comment-item" data-comment-id="' . $comment->id . '">';
                $html .= '<div class="card-header d-flex justify-content-between align-items-center">';
                $html .= '<div>';
                $html .= '<strong>' . htmlspecialchars($authorName) . '</strong>';
                $html .= ' <span class="badge badge-secondary">' . $authorType . '</span>';
                $html .= ' <span class="badge badge-primary">' . $typeLabel . '</span>';
                $html .= ' ' . $isInternal . ' ' . $isSystem;
                $html .= '</div>';
                $html .= '<div>';
                $html .= '<small class="text-muted">' . $comment->created_at->format('Y-m-d H:i:s') . ' ' . $edited . '</small>';
                if ($comment->user_id === auth('user-admin')->id()) {
                    $html .= ' <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="editComment(' . $comment->id . ')">编辑</button>';
                    $html .= ' <button type="button" class="btn btn-sm btn-outline-danger ml-1" onclick="deleteComment(' . $comment->id . ')">删除</button>';
                }
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div class="card-body">';
                $html .= '<div class="comment-content">' . nl2br(htmlspecialchars($comment->content)) . '</div>';
                if ($comment->attachments && !empty($comment->attachments)) {
                    $html .= '<div class="attachments mt-2"><strong>附件:</strong> ' . implode(', ', $comment->attachments) . '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';

            // 添加评论相关的JavaScript和模态框
            $html .= $modalHtml;
            $html .= $jsCode;

            return $html;
        })->unescape();

        return $show;
    }

    protected function getCurrentUser()
    {
        $userAdminUser = auth('user-admin')->user();
        if (!$userAdminUser) {
            return null;
        }

        // 直接返回认证的用户，因为user-admin guard使用的就是User模型
        return $userAdminUser;
    }

    /**
     * 添加评论
     */
    public function addComment(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        // 验证任务归属权限
        $user = $this->getCurrentUser();
        if ($user) {
            $userProjectIds = Project::where('user_id', $user->id)->pluck('id');
            if (!$userProjectIds->contains($task->project_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => '您没有权限访问此任务'
                ], 403);
            }
        }

        $request->validate([
            'content' => 'required|string|max:5000',
            'comment_type' => 'nullable|string|in:general,progress,issue,solution,question,note',
            'is_internal' => 'nullable|boolean',
        ]);

        $commentService = app(TaskCommentService::class);

        try {
            $comment = $commentService->create(
                $task,
                $request->only(['content', 'comment_type', 'is_internal']),
                $user
            );

            return response()->json([
                'status' => 'success',
                'message' => '评论添加成功',
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'author_name' => $comment->author_name,
                    'author_type' => $comment->author_type,
                    'comment_type' => $comment->comment_type?->label(),
                    'is_internal' => $comment->is_internal,
                    'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '评论添加失败: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 编辑评论
     */
    public function editComment(Request $request, $taskId, $commentId)
    {
        $task = Task::findOrFail($taskId);
        $comment = TaskComment::findOrFail($commentId);

        // 验证任务归属权限
        $user = $this->getCurrentUser();
        if ($user) {
            $userProjectIds = Project::where('user_id', $user->id)->pluck('id');
            if (!$userProjectIds->contains($task->project_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => '您没有权限访问此任务'
                ], 403);
            }
        }

        if ($comment->task_id !== $task->id) {
            return response()->json([
                'status' => 'error',
                'message' => '评论不属于此任务'
            ], 400);
        }

        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $commentService = app(TaskCommentService::class);

        try {
            $updatedComment = $commentService->update(
                $comment,
                $request->only(['content']),
                $user
            );

            return response()->json([
                'status' => 'success',
                'message' => '评论更新成功',
                'comment' => [
                    'id' => $updatedComment->id,
                    'content' => $updatedComment->content,
                    'edited_at' => $updatedComment->edited_at?->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '评论更新失败: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 删除评论
     */
    public function deleteComment($taskId, $commentId)
    {
        $task = Task::findOrFail($taskId);
        $comment = TaskComment::findOrFail($commentId);

        // 验证任务归属权限
        $user = $this->getCurrentUser();
        if ($user) {
            $userProjectIds = Project::where('user_id', $user->id)->pluck('id');
            if (!$userProjectIds->contains($task->project_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => '您没有权限访问此任务'
                ], 403);
            }
        }

        if ($comment->task_id !== $task->id) {
            return response()->json([
                'status' => 'error',
                'message' => '评论不属于此任务'
            ], 400);
        }

        $commentService = app(TaskCommentService::class);

        try {
            $commentService->delete($comment, $user);

            return response()->json([
                'status' => 'success',
                'message' => '评论删除成功'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '评论删除失败: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 获取评论模态框HTML
     */
    private function getCommentModalHtml()
    {
        return '
        <!-- 添加评论模态框 -->
        <div class="modal fade" id="addCommentModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">添加评论</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="commentForm">
                            <div class="form-group">
                                <label for="commentContent">评论内容</label>
                                <textarea class="form-control" id="commentContent" name="content" rows="4" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="commentType">评论类型</label>
                                <select class="form-control" id="commentType" name="comment_type">
                                    <option value="general">一般</option>
                                    <option value="progress">进度</option>
                                    <option value="issue">问题</option>
                                    <option value="solution">解决方案</option>
                                    <option value="question">疑问</option>
                                    <option value="note">备注</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="isInternal" name="is_internal">
                                    <label class="form-check-label" for="isInternal">
                                        内部评论（仅团队成员可见）
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" onclick="submitComment()">提交评论</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 编辑评论模态框 -->
        <div class="modal fade" id="editCommentModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">编辑评论</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="editCommentForm">
                            <input type="hidden" id="editCommentId">
                            <div class="form-group">
                                <label for="editCommentContent">评论内容</label>
                                <textarea class="form-control" id="editCommentContent" name="content" rows="4" required></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" onclick="updateComment()">更新评论</button>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * 获取评论相关JavaScript
     */
    private function getCommentJavaScript()
    {
        $taskId = request()->route('id');
        return "
        <script>
        function showAddCommentModal() {
            $('#addCommentModal').modal('show');
        }

        function submitComment() {
            const form = document.getElementById('commentForm');
            const formData = new FormData(form);

            // 转换checkbox值
            formData.set('is_internal', document.getElementById('isInternal').checked ? '1' : '0');

            // 获取CSRF token
            const headers = {};
            if (window.Dcat && window.Dcat.token) {
                headers['X-CSRF-TOKEN'] = window.Dcat.token;
            } else {
                const csrfMeta = document.querySelector('meta[name=\"csrf-token\"]');
                if (csrfMeta) {
                    headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
                }
            }

            fetch('/user-admin/tasks/{$taskId}/comments', {
                method: 'POST',
                body: formData,
                headers: headers
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#addCommentModal').modal('hide');
                    location.reload(); // 刷新页面显示新评论
                } else {
                    alert('错误: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('提交评论时发生错误');
            });
        }

        function editComment(commentId) {
            // 获取评论内容
            const commentElement = document.querySelector('[data-comment-id=\"' + commentId + '\"] .comment-content');
            const content = commentElement.textContent.trim();

            document.getElementById('editCommentId').value = commentId;
            document.getElementById('editCommentContent').value = content;
            $('#editCommentModal').modal('show');
        }

        function updateComment() {
            const commentId = document.getElementById('editCommentId').value;
            const content = document.getElementById('editCommentContent').value;

            // 获取CSRF token
            const headers = {
                'Content-Type': 'application/json'
            };
            if (window.Dcat && window.Dcat.token) {
                headers['X-CSRF-TOKEN'] = window.Dcat.token;
            } else {
                const csrfMeta = document.querySelector('meta[name=\"csrf-token\"]');
                if (csrfMeta) {
                    headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
                }
            }

            fetch('/user-admin/tasks/{$taskId}/comments/' + commentId, {
                method: 'PUT',
                headers: headers,
                body: JSON.stringify({
                    content: content
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#editCommentModal').modal('hide');
                    location.reload(); // 刷新页面显示更新的评论
                } else {
                    alert('错误: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('更新评论时发生错误');
            });
        }

        function deleteComment(commentId) {
            if (confirm('确定要删除这条评论吗？')) {
                // 获取CSRF token
                const headers = {};
                if (window.Dcat && window.Dcat.token) {
                    headers['X-CSRF-TOKEN'] = window.Dcat.token;
                } else {
                    const csrfMeta = document.querySelector('meta[name=\"csrf-token\"]');
                    if (csrfMeta) {
                        headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
                    }
                }

                fetch('/user-admin/tasks/{$taskId}/comments/' + commentId, {
                    method: 'DELETE',
                    headers: headers
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload(); // 刷新页面移除删除的评论
                    } else {
                        alert('错误: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('删除评论时发生错误');
                });
            }
        }
        </script>";
    }
}
