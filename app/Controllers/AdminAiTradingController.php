<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\NotFoundException;
use App\Support\{Request,Response};

final class AdminAiTradingController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.ai-trading.index',[
            'title'=>'AI Trading',
            'categories'=>app('ai_trading')->adminCategories(),
            'purchases'=>app('ai_trading')->adminPurchases(),
        ],'layouts.admin');
    }

    public function store(Request $request): Response
    {
        try{
            $category=app('ai_trading')->createCategory($request->all(),(int)$_SESSION['user_id']);
            $this->flash('success',$category['name'].' created with its first 50 AI trading codes.');
        }catch(\Throwable $e){
            $this->flash('error',$e->getMessage());
        }
        return $this->redirect('admin.ai-trading');
    }

    public function edit(Request $request): Response
    {
        $category=app('ai_trading')->findCategory((string)$request->route('category'));
        if(!$category) throw new NotFoundException('AI trading category not found.');
        return $this->view('admin.ai-trading.edit',[
            'title'=>'Edit '.$category['name'],
            'category'=>$category,
        ],'layouts.admin');
    }

    public function update(Request $request): Response
    {
        $ref=(string)$request->route('category');
        try{
            app('ai_trading')->updateCategory($ref,$request->all());
            $this->flash('success','AI trading category updated. Existing purchases keep their original price, profit, duration and FX snapshots.');
        }catch(\Throwable $e){
            $this->flash('error',$e->getMessage());
        }
        return $this->redirect('admin.ai-trading.edit',['category'=>$ref]);
    }
}
