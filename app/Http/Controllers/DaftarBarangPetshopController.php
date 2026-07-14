<?php

namespace App\Http\Controllers;

use App\Exports\MultipleSheetUploadDaftarBarangPetShop;
use App\Exports\RekapDaftarBarangPetShop;
use App\Imports\MultipleSheetImportDaftarBarangPetShop;
use App\Models\ListofItemsPetShop;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Validator;
use App\Models\Branch;

class DaftarBarangPetshopController extends Controller
{
    public function index(Request $request)
    {
        $items_per_page = 50;

        $page = $request->page;

        $item = DB::table('list_of_item_pet_shops as loi')
            ->join('users', 'loi.user_id', '=', 'users.id')
            ->join('branches', 'loi.branch_id', '=', 'branches.id')
            ->select('loi.id',
                'loi.item_name',
                DB::raw("TRIM(loi.total_item)+0 as total_item"),
                DB::raw("TRIM(loi.limit_item)+0 as limit_item"),
                DB::raw("TRIM(loi.diff_item)+0 as diff_item"),
                DB::raw('(CASE WHEN loi.expired_date = "0000-00-00" THEN "" ELSE DATE_FORMAT(loi.expired_date, "%d/%m/%Y") END) as expired_date'),
                DB::raw('(CASE WHEN loi.expired_date = "0000-00-00" THEN 60 ELSE loi.diff_expired_days END)+0 as diff_expired_days'),
                'branches.id as branch_id',
                'branches.branch_name',
                'users.id as user_id',
                'users.fullname as created_by',
                DB::raw("DATE_FORMAT(loi.created_at, '%d %b %Y') as created_at"))
            ->where('loi.isDeleted', '=', 0);

        if ($request->keyword) {

            $item = $item->where('loi.item_name', 'like', '%' . $request->keyword . '%');
        }

        if ($request->branch_id && $request->user()->role == 'admin') {
            $item = $item->where('loi.branch_id', '=', $request->branch_id);
        }

        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            $item = $item->where('loi.branch_id', '=', $request->user()->branch_id);
        }

        if ($request->orderby) {
            $item = $item->orderBy($request->column, $request->orderby);
        }

        $item = $item->orderBy('loi.diff_item', 'ASC')
            ->orderBy('loi.diff_expired_days', 'ASC')
            ->orderBy('loi.id', 'DESC');

        $offset = ($page - 1) * $items_per_page;

        $count_data = $item->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $item = $item->offset(0)->limit($items_per_page)->get();
        } else {
            $item = $item->offset($offset)->limit($items_per_page)->get();
        }

        $total_paging = $count_data / $items_per_page;

        return response()->json(['total_paging' => ceil($total_paging),
            'data' => $item], 200);
    }

    public function create(Request $request)
    {
        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_barang' => 'required|string|min:3|max:50',
            'jumlah_barang' => 'required|numeric|min:0',
            'limit_barang' => 'required|numeric|min:0',
            'tanggal_expired' => 'required|date_format:d/m/Y',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return response()->json([
                'message' => 'Barang yang dimasukkan tidak valid!',
                'errors' => $errors,
            ], 422);
        }

        $branchId = $request->cabang;
        $result_branch = json_decode($branchId, true);

        if (count($result_branch) == 0) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Data Cabang Harus dipilih minimal 1!'],
            ], 422);
        }

        foreach ($result_branch as $key_branch) {

            $check_branch = DB::table('list_of_item_pet_shops')
                ->where('branch_id', '=', $key_branch)
                ->where('item_name', '=', $request->nama_barang)
                ->where('isDeleted', '=', 0)
                ->count();

            if ($check_branch > 0) {

                return response()->json([
                    'message' => 'The data was invalid.',
                    'errors' => ['Data sudah ada!'],
                ], 422);
            }
        }

        $exp_date = Carbon::parse(Carbon::createFromFormat('d/m/Y', $request->tanggal_expired)->format('Y/m/d'));

        // if ($request->jumlah_barang - $request->limit_barang < 0) {
        //     return response()->json([
        //         'message' => 'The data was invalid.',
        //         'errors' => ['Jumlah Barang kurang dari Limit Barang!'],
        //     ], 422);

        // } elseif (Carbon::parse(now())->diffInDays($exp_date, false) < 0) {
        //     return response()->json([
        //         'message' => 'The data was invalid.',
        //         'errors' => ['Tanggal Kedaluwarsa kurang dari Tanggal Hari ini!'],
        //     ], 422);
        // }

        foreach ($result_branch as $key_branch) {

            $item = ListofItemsPetShop::create([
                'item_name' => $request->nama_barang,
                'total_item' => $request->jumlah_barang,
                'branch_id' => $key_branch,
                'user_id' => $request->user()->id,
                'limit_item' => $request->limit_barang,
                'expired_date' => $exp_date,
                'diff_item' => $request->jumlah_barang - $request->limit_barang,
                'diff_expired_days' => Carbon::parse(now())->diffInDays($exp_date, false),
            ]);
        }

        return response()->json(
            [
                'message' => 'Tambah Daftar Barang Pet Shop Berhasil!',
            ], 200
        );
    }

    public function update(Request $request)
    {
        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_barang' => 'required|string|min:3|max:50',
            'jumlah_barang' => 'required|numeric|min:0',
            'limit_barang' => 'required|numeric|min:0',
            'tanggal_expired' => 'required|date_format:d/m/Y',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return response()->json([
                'message' => 'Barang yang dimasukkan tidak valid!',
                'errors' => $errors,
            ], 422);
        }

        $item = ListofItemsPetShop::find($request->id);

        if (is_null($item)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak ditemukan!'],
            ], 404);
        }

        $find_duplicate = db::table('list_of_item_pet_shops')
            ->where('branch_id', '=', $request->cabang_id)
            ->where('item_name', '=', $request->nama_barang)
            ->where('id', '!=', $request->id)
            ->count();

        if ($find_duplicate != 0) {

            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data sudah ada!'],
            ], 422);

        }

        $check_stock = DB::table('list_of_item_pet_shops')
            ->select('total_item')
            ->where('id', '=', $request->id)
            ->first();

        if (is_null($check_stock)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data stock tidak ditemukan!'],
            ], 404);
        }

        $exp_date = Carbon::parse(Carbon::createFromFormat('d/m/Y', $request->tanggal_expired)->format('Y/m/d'));

        $item->item_name = $request->nama_barang;
        $item->total_item = $request->jumlah_barang;
        $item->user_update_id = $request->user()->id;
        $item->updated_at = Carbon::now();
        $item->limit_item = $request->limit_barang;
        $item->expired_date = $exp_date;
        $item->diff_item = $request->jumlah_barang - $request->limit_barang;
        $item->diff_expired_days = Carbon::parse(now())->diffInDays($exp_date, false);
        $item->save();

        return response()->json([
            'message' => 'Berhasil mengubah Barang Pet SHop',
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

        $item = ListofItemsPetShop::find($request->id);

        if (is_null($item)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data tidak ditemukan!'],
            ], 404);
        }

        $item->isDeleted = true;
        $item->deleted_by = $request->user()->fullname;
        $item->deleted_at = Carbon::now();
        $item->save();
        //$item->delete();

        return response()->json([
            'message' => 'Berhasil menghapus Barang',
        ], 200);
    }

    public function download_template(Request $request)
    {
        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        return (new MultipleSheetUploadDaftarBarangPetShop())->download('Template Daftar Barang Pet Shop.xlsx');
    }

    public function upload_template(Request $request)
    {
        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        $this->validate($request, [
            'file' => 'required|mimes:xls,xlsx',
        ]);

        $rows = Excel::toArray(new MultipleSheetImportDaftarBarangPetShop($request->user()->id), $request->file('file'));
        $result = $rows[0];

        foreach ($result as $key_result) {

            $check_branch = DB::table('list_of_item_pet_shops')
                ->where('branch_id', '=', $key_result['kode_cabang_barang'])
                ->where('item_name', '=', $key_result['nama_barang'])
                ->where('isDeleted', '=', 0)
                ->count();

            if ($check_branch > 0) {

                return response()->json([
                    'message' => 'The data was invalid.',
                    'errors' => ['Data ' . $key_result['nama_barang'] . ' sudah ada!'],
                ], 422);
            }
        }

        $file = $request->file('file');

        Excel::import(new MultipleSheetImportDaftarBarangPetShop($request->user()->id), $file);

        return response()->json([
            'message' => 'Berhasil mengupload Barang',
        ], 200);
    }

    public function generate_excel(Request $request)
    {
        $date = Carbon::now()->format('d-m-y');

        $branchId = "";

        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis') {
            $branchId = $request->user()->branch_id;
        } else {
            $branchId = $request->branch_id;
        }

        $listBranch = Branch::find($branchId);

        $filename = "";

        if ($listBranch) {
            $filename = 'Rekap Daftar Barang Pet Shop Cabang ' . $listBranch->branch_name . ' ' . $date . '.xlsx';
        } else {
            $filename = 'Rekap Daftar Barang Pet Shop ' . $date . '.xlsx';
        }

        return (new RekapDaftarBarangPetShop($request->orderby, $request->column, $request->keyword, $branchId, $request->user()->role))
            ->download($filename);
    }

    //untuk barang pet shop
    public function index_limit(Request $request)
    {
        $items_per_page = 50;

        $page = $request->page;

        $item = DB::table('list_of_item_pet_shops as loi')
            ->join('users', 'loi.user_id', '=', 'users.id')
            ->join('branches', 'loi.branch_id', '=', 'branches.id')
            ->select('loi.id',
                'loi.item_name',
                DB::raw("TRIM(loi.total_item)+0 as total_item"),
                DB::raw("TRIM(loi.limit_item)+0 as limit_item"),
                DB::raw("TRIM(loi.diff_item)+0 as diff_item"),
                DB::raw('(CASE WHEN loi.expired_date = "0000-00-00" THEN "" ELSE DATE_FORMAT(loi.expired_date, "%d/%m/%Y") END) as expired_date'),
                DB::raw('(CASE WHEN loi.expired_date = "0000-00-00" THEN 60 ELSE loi.diff_expired_days END)+0 as diff_expired_days'),
                'branches.id as branch_id',
                'branches.branch_name',
                'users.id as user_id',
                'users.fullname as created_by',
                DB::raw("DATE_FORMAT(loi.created_at, '%d %b %Y') as created_at"))
            ->where('loi.isDeleted', '=', 0)
            ->where('loi.diff_item', '<', 0);

        if ($request->keyword) {
            $item = $item->where('loi.item_name', 'like', '%' . $request->keyword . '%');
        }

        if ($request->branch_id && $request->user()->role == 'admin') {

            $item = $item->where('loi.branch_id', '=', $request->branch_id);
        }

        if ($request->user()->role == 'dokter' || $request->user()->role == 'resepsionis' || $request->user()->role == 'paramedis') {
            $item = $item->where('loi.branch_id', '=', $request->user()->branch_id);
        }

        if ($request->orderby) {
            $item = $item->orderBy($request->column, $request->orderby);
        }

        $item = $item->orderBy('loi.diff_item', 'ASC')
            ->orderBy('loi.diff_expired_days', 'ASC')
            ->orderBy('loi.id', 'DESC');

        $offset = ($page - 1) * $items_per_page;

        $count_data = $item->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $item = $item->offset(0)->limit($items_per_page)->get();
        } else {
            $item = $item->offset($offset)->limit($items_per_page)->get();
        }

        $total_paging = $count_data / $items_per_page;

        return response()->json(['total_paging' => ceil($total_paging),
            'data' => $item], 200);
    }
}
