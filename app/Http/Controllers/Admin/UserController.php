<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;
use App\Http\Requests\UserRequest;
use Datatable;
use Laracasts\Flash\Flash;
use Kris\LaravelFormBuilder\FormBuilder;
use Auth;

class UserController extends Controller
{
	/**
	 * Display a listing of the users.
	 *
	 * @return Response
	 */
	public function index()
	{
        $table = $this->setDatatable();
        return view('admin.users.index', compact('table'));
	}

	/**
	 * Show the form for creating a new user.
	 *
     * @param FormBuilder $formBuilder
	 * @return Response
	 */
	public function create(FormBuilder $formBuilder)
	{
        $form = $formBuilder->create('App\Forms\UsersForm', [
            'method' => 'POST',
            'url' => route('admin.user.store')
        ]);
        return view('admin.users.create', compact('form'));
	}

    /**
     * Store a newly created user in storage
     *
     * @param UserRequest $request
     * @return Response
     */
    public function store(UserRequest $request)
    {
        $data = $this->storeImage($request, 'picture');
        User::create($data) == true ? Flash::success(trans('admin.create.success')) :
            Flash::error(trans('admin.create.fail'));
        return redirect(route('admin.user.index'));
    }

    /**
     * Display the specified user.
     *
     * @param User $user
     * @return Response
     */
    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param User $user
     * @param FormBuilder $formBuilder
     * @return Response
     */
    public function edit(User $user, FormBuilder $formBuilder)
    {
        $form = $formBuilder->create('App\Forms\UsersForm', [
            'method' => 'PATCH',
            'url' => route('admin.user.update', ['id' => $user->id]),
            'model' => $user
        ]);
        return view('admin.users.edit', compact('form', 'user'));
    }

    /**
     * Update the specified user in storage.
     *
     * @param User $user
     * @param UserRequest $request
     * @return Response
     */
    public function update(User $user, UserRequest $request)
    {
        $data = $this->storeImage($request, 'picture');
        $user->fill($data);
        $user->save() == true ? Flash::success(trans('admin.update.success')) :
            Flash::error(trans('admin.update.fail'));
        return redirect(route('admin.user.index'));
    }

	/**
	 * Remove the specified user from storage.
	 *
	 * @param User $user
	 * @return Response
	 */
	public function destroy(User $user)
	{
        if($user->id != Auth::user()->id)
        {

            $user->delete() == true ? Flash::success(trans('admin.delete.success')) :
                Flash::error(trans('admin.delete.fail'));
        }
        else
        {
            Flash::error(trans('admin.delete.self'));
        }
        return redirect(route('admin.user.index'));
	}

    /**
     * Save image to uploads folder and change the name to something unique
     *
     * @param UserRequest $request
     * @param $field
     * @return array
     */
    private function storeImage(UserRequest $request, $field)
    {
        $data = $request->except([$field]);
        if($request->file($field))
        {
            $file = $request->file($field);
            $request->file($field);
            $fileName = rename_file($file->getClientOriginalName(), $file->getClientOriginalExtension());
            $path = '/uploads/' . str_plural($field);
            $move_path = public_path() . $path;
            $file->move($move_path, $fileName);
            $data[$field] = $path . $fileName;
        }
        return $data;
    }

    private function setDatatable()
    {
        return Datatable::table()
            ->addColumn(trans('admin.fields.user.name'), trans('admin.fields.user.ip_address'), trans('admin.fields.user.logged_in_at'), trans('admin.fields.user.logged_out_at'))
            ->addColumn(trans('admin.ops.name'))
            ->setUrl(route('admin.user.table'))
            ->setOptions(array('sPaginationType' => 'bs_normal', 'oLanguage' => trans('admin.datatables')))
            ->render();
    }

    public function getDatatable()
    {
        return Datatable::collection(User::all())
            ->showColumns('name', 'ip_address')
            ->addColumn('logged_in_at', function($model)
            {
                return $model->logged_in_at->diffForHumans();
            })
            ->addColumn('logged_out_at', function($model)
            {
                return $model->logged_out_at->diffForHumans();
            })
            ->addColumn('',function($model)
            {
                return get_ops('user', $model->id);
            })
            ->searchColumns('name','ip_address')
            ->orderColumns('name','logged_in_at','logged_out_at')
            ->make();
    }

}
