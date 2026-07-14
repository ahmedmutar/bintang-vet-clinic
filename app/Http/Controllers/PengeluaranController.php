<?php

namespace App\Http\Controllers;

use App\Models\Expenses;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Response;
use Validator;

class PengeluaranController extends Controller
{
    public function index(Request $request)
    {

        $items_per_page = 50;

        $page = $request->page;

        $expenses = DB::table('expenses as e')
            ->join('users', 'e.user_id', '=', 'users.id')
            ->join('users as user_spender', 'e.user_id_spender', '=', 'user_spender.id')
        //->join('branches as b', 'user_spender.branch_id', '=', 'b.id')
            ->select(
                'e.id as id',
                DB::raw("DATE_FORMAT(e.date_spend, '%d %b %Y %H:%i:%s') as date_spend"),
                'user_spender.fullname',
                'e.user_id_spender',
                'e.item_name',
                'e.quantity',
                DB::raw("TRIM(e.amount)+0 as amount"),
                DB::raw("TRIM(e.amount_overall)+0 as amount_overall"),
                'users.fullname as created_by',
                DB::raw("DATE_FORMAT(e.created_at, '%d %b %Y %H:%i:%s') as created_at"));

        if ($request->keyword) {
            $res = $this->Search($request);
            if ($res) {
                $expenses = $expenses->where($res, 'like', '%' . $request->keyword . '%');
            } else {
                $expenses = [];
                return response()->json(['total_paging' => 0,
                    'data' => $expenses,
                    'amount_overall' => 0], 200);
            }
        }

        if ($request->branch_id && $request->user()->role == 'admin') {
            $expenses = $expenses->where('user_spender.branch_id', '=', $request->branch_id);
        }

        if ($request->month && $request->year) {
            $expenses = $expenses->where(DB::raw("MONTH(e.date_spend)"), $request->month)
                ->where(DB::raw("YEAR(e.date_spend)"), $request->year);
        }

        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            $expenses = $expenses->where('user_spender.branch_id', '=', $request->user()->branch_id);
        }

        if ($request->orderby) {
            $expenses = $expenses->orderBy($request->column, $request->orderby);
        } else {
            $expenses = $expenses->orderBy('e.id', 'desc');
        }

        $offset = ($page - 1) * $items_per_page;

        $count_data = $expenses->count();
        $count_result = $count_data - $offset;

        $data_before_limit = $expenses->get();

        $amount_overall = 0;

        foreach ($data_before_limit as $res) {
            $amount_overall += $res->amount_overall;
        }

        if ($count_result < 0) {
            $expenses = $expenses->offset(0)->limit($items_per_page)->get();
        } else {
            $expenses = $expenses->offset($offset)->limit($items_per_page)->get();
        }

        $total_paging = $count_data / $items_per_page;

        return response()->json(['total_paging' => ceil($total_paging),
            'data' => $expenses,
            'amount_overall' => $amount_overall], 200);

    }

    private function Search($request)
    {
        $temp_column = '';

        $data = DB::table('expenses as e')
            ->join('users', 'e.user_id', '=', 'users.id')
            ->join('users as user_spender', 'e.user_id_spender', '=', 'user_spender.id')
            ->join('branches as b', 'b.id', '=', 'user_spender.branch_id')
            ->select(
                'user_spender.fullname',
                'e.item_name'
            );

        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            $data = $data->where('b.id', '=', $request->user()->branch_id);
        }

        if ($request->keyword) {
            $data = $data->where('user_spender.fullname', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'user_spender.fullname';
            return $temp_column;
        }

        //=============================

        $data = DB::table('expenses as e')
            ->join('users', 'e.user_id', '=', 'users.id')
            ->join('users as user_spender', 'e.user_id_spender', '=', 'user_spender.id')
            ->join('branches as b', 'b.id', '=', 'user_spender.branch_id')
            ->select(
                'user_spender.fullname',
                'e.item_name'
            );

        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            $data = $data->where('b.id', '=', $request->user()->branch_id);
        }

        if ($request->keyword) {
            $data = $data->where('e.item_name', 'like', '%' . $request->keyword . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'e.item_name';
            return $temp_column;
        }
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'date_spend' => 'required|date_format:d/m/Y',
            'user_id_spender' => 'required|numeric',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric',
            'items.*.amount' => 'required|numeric',
            'items.*.amount_overall' => 'required|numeric',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $tmp_items = $request->items;

        $items = json_decode($tmp_items, true);

        foreach ($items as $item) {

            $expense = Expenses::create([
                'date_spend' => Carbon::parse(Carbon::createFromFormat('d/m/Y', $request->date_spend)->format('Y/m/d')),
                'user_id_spender' => $request->user_id_spender,
                'item_name' => $item['item_name'],
                'quantity' => $item['quantity'],
                'amount' => $item['amount'],
                'user_id' => $request->user()->id,
                'amount_overall' => $item['amount_overall'],
            ]);

        }

        return response()->json(
            [
                'message' => 'Tambah Daftar Barang Berhasil!',
            ], 200
        );

    }

    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'user_id_spender' => 'required|numeric',
            'item_name' => 'required|string',
            'quantity' => 'required|numeric',
            'amount' => 'required|numeric',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $date = "";
        if (str_contains($request->date_spend, "/")) {

            $date = Carbon::parse(Carbon::createFromFormat('d/m/Y', $request->date_spend)->format('Y-m-d'));

        } else {
            $date = Carbon::parse(Carbon::createFromFormat('Y-m-d', $request->date_spend)->format('Y-m-d'));
        }

        $expense = Expenses::find($request->id);
        $expense->date_spend = $date;
        $expense->user_id_spender = $request->user_id_spender;
        $expense->item_name = $request->item_name;
        $expense->quantity = $request->quantity;
        $expense->amount = $request->amount;
        $expense->amount_overall = $request->amount * $request->quantity;

        $expense->user_update_id = $request->user()->id;
        $expense->updated_at = Carbon::now();

        $expense->save();

        return response()->json([
            'message' => 'Berhasil mengubah Data',
        ], 200);
    }

    public function delete(Request $request)
    {
        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $data = Expenses::where('id', '=', $request->id)
            ->count();

        if ($data == 0) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak ada ada!'],
            ], 422);
        }

        $data = Expenses::find($request->id);
        $data->delete();

        return response()->json([
            'message' => 'Berhasil menghapus Data',
        ], 200);
    }
}
