<?php

namespace App\Http\Controllers;

use App\Models\CheckUpFollowUp;
use App\Models\CheckUpResult;
use App\Models\DetailItemPatient;
use App\Models\DetailServicePatient;
use App\Models\Detail_medicine_group_check_up_result;
use App\Models\HistoryItemMovement;
use App\Models\ImagesCheckUpResults;
use App\Models\InPatient;
use App\Models\ListofItems;
use App\Models\ListofServices;
use App\Models\Registration;
use App\Models\TempCountItem;
use DB;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Validator;

class HasilPemeriksaanController extends Controller
{
    public function index(Request $request)
    {

        $items_per_page = 50;

        $page = $request->page;

        $data = DB::table('check_up_results')
            ->join('users', 'check_up_results.user_id', '=', 'users.id')
            ->join('registrations', 'check_up_results.patient_registration_id', '=', 'registrations.id')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('owners', 'patients.owner_id', '=', 'owners.id')
            ->leftJoin('complaints', 'registrations.complaint_id', '=', 'complaints.id')
            ->select(
                'check_up_results.id',
                'check_up_results.user_id',
                'registrations.id_number as registration_number',
                'patients.id as patient_id',
                'patients.id_member as patient_number',
                'patients.pet_category',
                'patients.pet_name',
                DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name'),
                'registrations.complaint_id',
                'registrations.other_complaint',
                DB::raw('CASE WHEN registrations.complaint_id = 11 AND registrations.other_complaint IS NOT NULL THEN registrations.other_complaint ELSE COALESCE(complaints.name, registrations.complaint) END AS complaint'),
                'check_up_results.status_finish',
                'check_up_results.status_pengabaran',
                'check_up_results.status_outpatient_inpatient',
                'users.fullname as created_by',
                DB::raw("DATE_FORMAT(check_up_results.created_at, '%d %b %Y %H:%i:%s') as created_at")
            )
            ->where('check_up_results.isDeleted', '=', 0);

        if ($request->keyword) {
            $keyword = '%' . $request->keyword . '%';
            $data = $data->where(function ($q) use ($keyword) {
                $q->where('registrations.id_number',  'like', $keyword)
                  ->orWhere('patients.id_member',      'like', $keyword)
                  ->orWhere('patients.pet_category',   'like', $keyword)
                  ->orWhere('patients.pet_name',       'like', $keyword)
                  ->orWhere('patients.owner_name',     'like', $keyword)
                  ->orWhere('owners.owner_name',       'like', $keyword)
                  ->orWhere('registrations.complaint', 'like', $keyword)
                  ->orWhere('users.fullname',          'like', $keyword);
            });
        }

        if ($request->user()->role == 'dokter') {
            $data = $data->where('users.branch_id', '=', $request->user()->branch_id);
        }

        if ($request->branch_id && $request->user()->role == 'admin') {
            $data = $data->where('users.branch_id', '=', $request->branch_id);
        }

        if ($request->orderby) {
            $data = $data->orderBy($request->column, $request->orderby);
        }

        $data = $data->orderBy('check_up_results.id', 'desc');

        $offset = ($page - 1) * $items_per_page;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($items_per_page)->get();
        } else {
            $data = $data->offset($offset)->limit($items_per_page)->get();
        }

        $total_paging = $count_data / $items_per_page;

        return response()->json([
            'total_paging' => ceil($total_paging),
            'data' => $data
        ], 200);
    }

    public function create(Request $request)
    {
        // if ($request->user()->role == 'resepsionis') {
        //     return response()->json([
        //         'message' => 'The user role was invalid.',
        //         'errors' => ['Akses User tidak diizinkan!'],
        //     ], 403);
        // }

        $check_up_result = CheckUpResult::where('patient_registration_id', '=', $request->patient_registration_id)
            ->where('isdeleted', '=', 1)
            ->first();

        if ($check_up_result) {

            $validate = Validator::make($request->all(), [
                'patient_registration_id' => 'required|numeric',
                'anamnesa' => 'nullable|string',
                'sign' => 'nullable|string',
                'diagnosa' => 'nullable|string',
                'status_finish' => 'nullable|bool',
                'status_pengabaran' => 'nullable|bool',
                'alasan_tidak_pengabaran' => 'required_if:status_pengabaran,0|nullable|string',
                'status_outpatient_inpatient' => 'nullable|bool',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }
        } else {
            $message_patient = [
                'patient_registration_id.unique' => 'Registrasi Pasien ini sudah pernah di input sebelumnya',
            ];

            $validate = Validator::make($request->all(), [
                'patient_registration_id' => 'required|numeric|unique:check_up_results,patient_registration_id',
                // 'anamnesa' => 'required|string|min:1',
                // 'sign' => 'required|string|min:1',
                // 'diagnosa' => 'required|string|min:1',
                'status_finish' => 'nullable|bool',
                'status_pengabaran' => 'nullable|bool',
                'alasan_tidak_pengabaran' => 'required_if:status_pengabaran,0|nullable|string',
                'status_outpatient_inpatient' => 'nullable|bool',
            ], $message_patient);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();

                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }
        }

        //validasi jasa
        $services = $request->service;
        $result_item = json_decode($services, true);

        if (count($result_item) == 0) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Data Jasa Harus dipilih minimal 1!'],
            ], 422);
        }

        foreach ($result_item as $key_service) {

            $check_service = ListofServices::find($key_service);

            if (is_null($check_service)) {
                return response()->json([
                    'message' => 'The data was invalid.',
                    'errors' => ['Data tidak ditemukan!'],
                ], 404);
            }

            $check_price_service = DB::table('price_services')
                ->select('list_of_services_id')
                ->where('id', '=', $key_service['price_service_id'])
                ->first();

            if (is_null($check_price_service)) {
                return response()->json([
                    'message' => 'The data was invalid.',
                    'errors' => ['Data Daftar Harga Jasa tidak ditemukan!'],
                ], 404);
            }

            $check_service_name = DB::table('list_of_services')
                ->select('service_name')
                ->where('id', '=', $check_price_service->list_of_services_id)
                ->first();

            if (is_null($check_service_name)) {
                return response()->json([
                    'message' => 'The data was invalid.',
                    'errors' => ['Data Daftar Jasa tidak ditemukan!'],
                ], 404);
            }

            if ($key_service['quantity'] <= 0) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Jumlah jasa ' . $check_service_name->service_name . ' belum diisi!'],
                ], 422);
            }

            if ($request->status_outpatient_inpatient == false && stripos($check_service_name->service_name, 'inap') !== false) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Jasa ' . $check_service_name->service_name . ' tidak dapat dipilih untuk pasien rawat jalan!'],
                ], 422);
            }
        }

        //validasi item
        if ($request->item) {

            $temp_item = $request->item;

            $result_item = json_decode($temp_item, true);

            foreach ($result_item as $res_group) {

                $check_medicine_group = DB::table('price_medicine_groups')
                    ->select('id')
                    ->where('id', '=', $res_group['medicine_group_id'])
                    ->first();

                if (is_null($check_medicine_group)) {
                    return response()->json([
                        'message' => 'The data was invalid.',
                        'errors' => ['Data Kelompok Obat tidak ditemukan!'],
                    ], 404);
                }

                foreach ($res_group['list_of_medicine'] as $value_item) {

                    $check_price_item = DB::table('price_items')
                        ->select('list_of_items_id')
                        ->where('id', '=', $value_item['price_item_id'])
                        ->first();

                    $check_storage = DB::table('list_of_items')
                        ->select('total_item')
                        ->where('id', '=', $check_price_item->list_of_items_id)
                        ->first();

                    if (is_null($check_storage)) {
                        return response()->json([
                            'message' => 'The data was invalid.',
                            'errors' => ['Data Jumlah Barang tidak ditemukan!'],
                        ], 404);
                    }

                    $check_storage_name = DB::table('list_of_items')
                        ->select('item_name')
                        ->where('id', '=', $check_price_item->list_of_items_id)
                        ->first();

                    if (is_null($check_storage_name)) {
                        return response()->json([
                            'message' => 'The data was invalid.',
                            'errors' => ['Data Jumlah Barang tidak ditemukan!'],
                        ], 404);
                    }

                    if ($value_item['quantity'] <= 0) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Jumlah barang ' . $check_storage_name->item_name . ' belum diisi!'],
                        ], 422);
                    }

                    if ($value_item['quantity'] > $check_storage->total_item) {
                        return response()->json([
                            'message' => 'The given data was invalid.',
                            'errors' => ['Jumlah stok ' . $check_storage_name->item_name . ' kurang atau habis!'],
                        ], 422);
                    }

                    $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                    if (is_null($list_of_items)) {
                        return response()->json([
                            'message' => 'The data was invalid.',
                            'errors' => ['Data tidak ditemukan!'],
                        ], 404);
                    }
                }
            }

            //disini
            foreach ($result_item as $value_data) {

                foreach ($value_data['list_of_medicine'] as $value_item) {

                    $check_temp_count_items = DB::table('temp_count_items')
                        ->where('price_item_id', '=', $value_item['price_item_id'])
                        ->where('user_id', '=', $request->user()->id)
                        ->sum('temp_count_items.quantity');

                    $check_temp_item = DB::table('temp_count_items')
                        ->where('price_item_id', '=', $value_item['price_item_id'])
                        ->where('user_id', '=', $request->user()->id)
                        ->first();

                    if (is_null($check_temp_item)) {

                        $service_list = TempCountItem::create([
                            'price_item_id' => $value_item['price_item_id'],
                            'quantity' => $value_item['quantity'],
                            'user_id' => $request->user()->id,
                        ]);
                    } else {

                        $adding_value = $check_temp_count_items;

                        $find_id = DB::table('temp_count_items')
                            ->select('id', 'quantity')
                            ->where('price_item_id', '=', $value_item['price_item_id'])
                            ->where('user_id', '=', $request->user()->id)
                            ->first();

                        $res_adding = $adding_value + $value_item['quantity'];

                        $find_price_item = TempCountItem::find($find_id->id);
                        $find_price_item->quantity = $res_adding;
                        $find_price_item->save();
                    }
                }
            }

            $find_temp = TempCountItem::where('user_id', '=', $request->user()->id)->get();

            $data_item = [];

            $data_item = $find_temp;

            foreach ($data_item as $find_stock) {

                $check_price_item = DB::table('price_items')
                    ->select('list_of_items_id')
                    ->where('id', '=', $find_stock['price_item_id'])
                    ->first();

                $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                $count_item = $list_of_items->total_item - $find_stock['quantity'];

                $detail_item = DB::table('temp_count_items')
                    ->where('user_id', $request->user()->id)->delete();

                if ($count_item < 0) {

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Jumlah stok ' . $list_of_items->item_name . ' kurang atau habis!'],
                    ], 422);
                }
            }
        }

        //validasi tambah gambar

        // $messages_images = [
        //     'filenames.required' => 'Gambar harus diisi!',
        //     'filenames.*.required' => 'Gambar harus diisi banyak!',
        // ];

        // $validator_image = Validator::make($request->all(), [
        //     'filenames' => 'required',
        //     'filenames.*' => 'required|mimes:jpg,png,jpeg',
        // ], $messages_images);

        // if ($validator_image->fails()) {
        //     $errors_image = $validator_image->errors()->all();

        //     return response()->json([
        //         'message' => 'Foto yang dimasukkan tidak valid!',
        //         'errors' => $errors_image,
        //     ], 422);
        // }

        if ($request->hasfile('filenames')) {

            $data_item = [];

            $files[] = $request->file('filenames');

            foreach ($files as $file) {

                foreach ($file as $fil) {

                    $file_size = $fil->getSize();

                    $file_size = $file_size / 1024;

                    $oldname = $fil->getClientOriginalName();

                    if ($file_size >= 5000) {

                        array_push($data_item, 'Foto ' . $oldname . ' lebih dari 5mb! Harap upload gambar dengan ukuran lebih kecil!');
                    }
                }
            }

            if ($data_item) {

                return response()->json([
                    'message' => 'Foto yang dimasukkan tidak valid!',
                    'errors' => $data_item,
                ], 422);
            }
        }

        $anamnesa = "";
        if (!is_null($request->anamnesa)) {
            $anamnesa = $request->anamnesa;
        }

        $sign = "";
        if (!is_null($request->sign)) {
            $sign = $request->sign;
        }

        $diagnosa = "";
        if (!is_null($request->diagnosa)) {
            $diagnosa = $request->diagnosa;
        }

        //insert data
        $item = CheckUpResult::create([
            'patient_registration_id' => $request->patient_registration_id,
            'anamnesa' => $anamnesa,
            'sign' => $sign,
            'diagnosa' => $diagnosa,
            'status_finish' => $request->status_outpatient_inpatient == false ? 1 : ($request->status_finish ?? 0),
            'status_pengabaran' => $request->status_pengabaran,
            'alasan_tidak_pengabaran' => $request->status_pengabaran == 0 ? ($request->alasan_tidak_pengabaran ?? '') : null,
            'status_outpatient_inpatient' => $request->status_outpatient_inpatient,
            'status_paid_off' => 0,
            'user_id' => $request->user()->id,
        ]);

        // if ($request->status_pengabaran == true) {

            $registration = Registration::find($request->patient_registration_id);
            $registration->user_update_id = $request->user()->id;
            $registration->acceptance_status = 3;
            $registration->updated_at = \Carbon\Carbon::now();
            $registration->save();
        // }

        $services = $request->service;
        $result_item = json_decode($services, true);

        foreach ($result_item as $key_service) {

            $service_list = DetailServicePatient::create([
                'check_up_result_id' => $item->id,
                'price_service_id' => $key_service['price_service_id'],
                'quantity' => $key_service['quantity'],
                'price_overall' => $key_service['price_overall'],
                'status_paid_off' => 0,
                'user_id' => $request->user()->id,
            ]);
        }

        if (!(is_null($request->item))) {

            $result_item = json_decode($request->item, true);

            foreach ($result_item as $res_group) {

                $group_list = Detail_medicine_group_check_up_result::create([
                    'check_up_result_id' => $item->id,
                    'medicine_group_id' => $res_group['medicine_group_id'],
                    'status_paid_off' => 0,
                    'quantity' => $res_group['quantity'],
                    'remark' => $res_group['remark'],
                    'user_id' => $request->user()->id,
                ]);

                foreach ($res_group['list_of_medicine'] as $value_item) {

                    $item_list = DetailItemPatient::create([
                        'check_up_result_id' => $item->id,
                        'medicine_group_id' => $res_group['medicine_group_id'],
                        'price_item_id' => $value_item['price_item_id'],
                        'quantity' => $value_item['quantity'],
                        'price_overall' => $value_item['price_overall'],
                        'user_id' => $request->user()->id,
                        'detail_medicine_group_id' => $group_list->id,
                    ]);

                    $check_price_item = DB::table('price_items')
                        ->select('list_of_items_id')
                        ->where('id', '=', $value_item['price_item_id'])
                        ->first();

                    $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                    $count_item = $list_of_items->total_item - $value_item['quantity'];

                    $list_of_items->total_item = $count_item;
                    $list_of_items->diff_item = $count_item - $list_of_items->limit_item;
                    $list_of_items->user_update_id = $request->user()->id;
                    $list_of_items->updated_at = \Carbon\Carbon::now();
                    $list_of_items->save();

                    $item_history = HistoryItemMovement::create([
                        'price_item_id' => $value_item['price_item_id'],
                        'quantity' => $value_item['quantity'],
                        'status' => 'kurang',
                        'user_id' => $request->user()->id,
                    ]);
                }
            }
        }

        if ($request->status_outpatient_inpatient == true && $request->inpatient != "") {

            $item_list = InPatient::create([
                'check_up_result_id' => $item->id,
                'description' => $request->inpatient,
                'user_id' => $request->user()->id,
            ]);
        }

        $detail_item = DB::table('temp_count_items')
            ->where('user_id', $request->user()->id)->delete();

        if ($request->hasfile('filenames')) {
            foreach ($request->file('filenames') as $file) {

                foreach ($file as $fil) {

                    $name = $fil->hashName();

                    $fil->move(public_path() . '/image_check_up_result/', $name);

                    $fileName = "/image_check_up_result/" . $name;

                    $imageFile = new ImagesCheckUpResults();
                    $imageFile->image = $fileName;
                    $imageFile->check_up_result_id = $item->id;
                    $imageFile->user_id = $request->user()->id;
                    $imageFile->save();
                }
            }
        }

        return response()->json(
            [
                'message' => 'Tambah Data Berhasil!',
            ],
            200
        );
    }

    public function detail(Request $request)
    {
        $data = CheckUpResult::find($request->id);

        if (is_null($data)) {

            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data Hasil Pemeriksaan tidak ditemukan!'],
            ], 404);
        }

        $registration = DB::table('registrations')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('owners', 'patients.owner_id', '=', 'owners.id')
            ->leftJoin('complaints', 'registrations.complaint_id', '=', 'complaints.id')
            ->select(
                'registrations.id_number as registration_number',
                'patients.id as patient_id',
                'patients.id_member as patient_number',
                'patients.pet_category',
                'patients.pet_name',
                'patients.pet_gender',
                'registrations.pet_year_age',
                'registrations.pet_month_age',
                'registrations.pet_day_age',
                DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name'),
                DB::raw('(CASE WHEN patients.owner_address = "" THEN owners.owner_address ELSE patients.owner_address END) AS owner_address'),
                DB::raw('(CASE WHEN patients.owner_phone_number = "" THEN owners.owner_phone_number ELSE patients.owner_phone_number END) AS owner_phone_number'),
                'registrations.complaint_id',
                'registrations.other_complaint',
                DB::raw('CASE WHEN registrations.complaint_id = 11 AND registrations.other_complaint IS NOT NULL THEN registrations.other_complaint ELSE COALESCE(complaints.name, registrations.complaint) END AS complaint'),
                'registrations.registrant'
            )
            ->where('registrations.id', '=', $data->patient_registration_id)
            ->first();

        $data->registration = $registration;

        $user = DB::table('check_up_results')
            ->join('users', 'check_up_results.user_id', '=', 'users.id')
            ->select('users.id as user_id', 'users.username as username')
            ->where('users.id', '=', $data->user_id)
            ->first();

        $data->user = $user;

        $services = DB::table('detail_service_patients')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->join('list_of_services', 'price_services.list_of_services_id', '=', 'list_of_services.id')
            ->join('service_categories', 'list_of_services.service_category_id', '=', 'service_categories.id')
            ->join('users', 'detail_service_patients.user_id', '=', 'users.id')
            ->select(
                'detail_service_patients.id as detail_service_patient_id',
                'price_services.id as price_service_id',
                'list_of_services.id as list_of_service_id',
                'list_of_services.service_name',
                'detail_service_patients.quantity',
                DB::raw("TRIM(detail_service_patients.price_overall)+0 as price_overall"),
                'service_categories.category_name',
                DB::raw("TRIM(price_services.selling_price)+0 as selling_price"),
                'users.fullname as created_by',
                DB::raw("DATE_FORMAT(detail_service_patients.created_at, '%d %b %Y') as created_at"),
                'detail_service_patients.status_paid_off'
            )
            ->where('detail_service_patients.check_up_result_id', '=', $data->id)
            ->orderBy('detail_service_patients.id', 'desc')
            ->get();

        $data['services'] = $services;

        $item = DB::table('detail_medicine_group_check_up_results')
            ->join('price_medicine_groups', 'detail_medicine_group_check_up_results.medicine_group_id', '=', 'price_medicine_groups.id')
            ->join('medicine_groups', 'price_medicine_groups.medicine_group_id', '=', 'medicine_groups.id')
            ->join('branches', 'medicine_groups.branch_id', '=', 'branches.id')
            ->select(
                'detail_medicine_group_check_up_results.id as id',
                'price_medicine_groups.id as price_medicine_group_id',
                DB::raw("TRIM(price_medicine_groups.selling_price)+0 as selling_price"),
                'detail_medicine_group_check_up_results.medicine_group_id as medicine_group_id',
                'detail_medicine_group_check_up_results.quantity as quantity',
                'detail_medicine_group_check_up_results.remark as remark',
                'medicine_groups.group_name',
                'branches.id as branch_id',
                'branches.branch_name'
            )
            ->where('detail_medicine_group_check_up_results.check_up_result_id', '=', $data->id)
            ->get();

        foreach ($item as $value) {

            $detail_item = DB::table('detail_item_patients')
                ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                ->join('category_item', 'list_of_items.category_item_id', '=', 'category_item.id')
                ->join('unit_item', 'list_of_items.unit_item_id', '=', 'unit_item.id')
                ->join('users', 'detail_item_patients.user_id', '=', 'users.id')
                ->select(
                    'detail_item_patients.id as detail_item_patients_id',
                    'list_of_items.id as list_of_item_id',
                    'price_items.id as price_item_id',
                    'list_of_items.item_name',
                    'detail_item_patients.quantity',
                    DB::raw("TRIM(detail_item_patients.price_overall)+0 as price_overall"),
                    'unit_item.unit_name',
                    'category_item.category_name',
                    DB::raw("TRIM(price_items.selling_price)+0 as selling_price"),
                    'users.fullname as created_by',
                    DB::raw("DATE_FORMAT(detail_item_patients.created_at, '%d %b %Y') as created_at")
                )
                ->where('detail_item_patients.detail_medicine_group_id', '=', $value->id)
                ->orderBy('detail_item_patients.id', 'asc')
                ->get();

            $value->list_of_medicine = $detail_item;
        }

        $data['item'] = $item;

        $inpatient = DB::table('in_patients')
            ->join('users', 'in_patients.user_id', '=', 'users.id')
            ->select(
                'in_patients.description',
                DB::raw("DATE_FORMAT(in_patients.created_at, '%d %b %Y') as created_at"),
                'users.fullname as created_by'
            )
            ->where('in_patients.check_up_result_id', '=', $data->id)
            ->get();

        $data['inpatient'] = $inpatient;

        $image = DB::table('images_check_up_results')
            ->select('images_check_up_results.id as image_id', 'images_check_up_results.image')
            ->where('check_up_result_id', '=', $data->id)
            ->get();

        $data['images'] = $image;

        return response()->json($data, 200);
    }

    public function update(Request $request)
    {

        if ($request->user()->role == 'resepsionis') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Akses User tidak diizinkan!'],
            ], 403);
        }

        //validasi data hasil pemeriksaaan
        $validate = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'patient_registration_id' => 'required|numeric',
            'anamnesa' => 'nullable|string',
            'sign' => 'nullable|string',
            'diagnosa' => 'nullable|string',
            'status_outpatient_inpatient' => 'nullable|bool',
            'status_finish' => 'nullable|bool',
            'status_pengabaran' => 'nullable|bool',
            'alasan_tidak_pengabaran' => 'required_if:status_pengabaran,0|nullable|string',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $check_up_result = CheckUpResult::find($request->id);

        if (is_null($check_up_result)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data Hasil Pemeriksaan tidak ada!'],
            ], 404);
        }

        if ($request->user()->role === 'dokter') {
            if ((int)$check_up_result->user_id !== (int)$request->user()->id) {
                return response()->json([
                    'message' => 'The user role was invalid.',
                    'errors' => ['Anda tidak memiliki akses untuk mengubah data hasil pemeriksaan dokter lain!'],
                ], 403);
            }
            if (\Carbon\Carbon::parse($check_up_result->created_at)->diffInHours(\Carbon\Carbon::now()) >= 24) {
                return response()->json([
                    'message' => 'The user role was invalid.',
                    'errors' => ['Waktu edit sudah melebihi 24 jam sejak pembuatan!'],
                ], 403);
            }
        }

        //validasi data jasa

        $temp_services = $request->service;

        $services = json_decode(json_encode($temp_services), true);

        if (count($services) == 0) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['Data Jasa Harus dipilih minimal 1!'],
            ], 422);
        }

        foreach ($services as $key_service) {

            if (!(is_null($key_service['id']))) {

                $check_price_service = DB::table('price_services')
                    ->select('list_of_services_id')
                    ->where('id', '=', $key_service['price_service_id'])
                    ->first();

                if (is_null($check_price_service)) {
                    return response()->json([
                        'message' => 'The data was invalid.',
                        'errors' => ['Data Harga Jasa tidak ditemukan!'],
                    ], 404);
                }

                $check_service = ListofServices::find($check_price_service->list_of_services_id);

                if (is_null($check_service)) {
                    return response()->json([
                        'message' => 'The data was invalid.',
                        'errors' => ['Data Daftar Jasa tidak ditemukan!'],
                    ], 404);
                }

                $check_service_name = DB::table('list_of_services')
                    ->select('service_name')
                    ->where('id', '=', $check_price_service->list_of_services_id)
                    ->first();

                if (is_null($check_service_name)) {
                    return response()->json([
                        'message' => 'The data was invalid.',
                        'errors' => ['Data Daftar Jasa tidak ditemukan!'],
                    ], 404);
                }

                if ($key_service['quantity'] <= 0) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Jumlah jasa ' . $check_service_name->service_name . ' belum diisi!'],
                    ], 422);
                }
            } else {

                $check_price_service = DB::table('price_services')
                    ->select('list_of_services_id')
                    ->where('id', '=', $key_service['price_service_id'])
                    ->first();

                if (is_null($check_price_service)) {
                    return response()->json([
                        'message' => 'The data was invalid.',
                        'errors' => ['Data Harga Jasa Tidak ditemukan!'],
                    ], 404);
                }

                $check_service_name = DB::table('list_of_services')
                    ->select('service_name')
                    ->where('id', '=', $check_price_service->list_of_services_id)
                    ->first();

                if (is_null($check_service_name)) {
                    return response()->json([
                        'message' => 'The data was invalid.',
                        'errors' => ['Data Daftar Jasa tidak ditemukan!'],
                    ], 404);
                }

                if ($key_service['quantity'] <= 0) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Jumlah jasa ' . $check_service_name->service_name . ' belum diisi!'],
                    ], 422);
                }
            }

            if ($request->status_outpatient_inpatient == false && stripos($check_service_name->service_name, 'inap') !== false) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Jasa ' . $check_service_name->service_name . ' tidak dapat dipilih untuk pasien rawat jalan!'],
                ], 422);
            }
        }

        //validasi data barang

        if ($request->item) {

            $temp_item = $request->item;

            $result_item = json_decode(json_encode($temp_item), true);

            foreach ($result_item as $res_medicine_group) {

                if ($res_medicine_group['id']) {

                    $check_detail_medicine_group = DB::table('detail_medicine_group_check_up_results')
                        ->where('id', '=', $res_medicine_group['id'])
                        ->first();

                    if (is_null($check_detail_medicine_group)) {
                        return response()->json([
                            'message' => 'The data was invalid.',
                            'errors' => ['Data Kelompok Obat tidak ditemukan asdsad!'],
                        ], 404);
                    }
                }

                $check_price_medicine_group = DB::table('price_medicine_groups')
                    ->select('id')
                    ->where('id', '=', $res_medicine_group['medicine_group_id'])
                    ->first();

                if (is_null($check_price_medicine_group)) {
                    return response()->json([
                        'message' => 'The data was invalid.',
                        'errors' => ['Data Kelompok Obat tidak ditemukan!'],
                    ], 404);
                }

                foreach ($res_medicine_group['list_of_medicine'] as $value_item) {

                    if (is_null($value_item['id'])) {

                        //kalau data baru
                        $check_price_item = DB::table('price_items')
                            ->select('list_of_items_id')
                            ->where('id', '=', $value_item['price_item_id'])
                            ->first();

                        if (is_null($check_price_item)) {
                            return response()->json([
                                'message' => 'The data was invalid.',
                                'errors' => ['Data Harga Barang tidak ditemukan!'],
                            ], 404);
                        }

                        $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                        if (is_null($list_of_items)) {
                            return response()->json([
                                'message' => 'The data was invalid.',
                                'errors' => ['Data Daftar Barang tidak ditemukan!'],
                            ], 404);
                        }

                        $check_storage = DB::table('list_of_items')
                            ->select('total_item')
                            ->where('id', '=', $check_price_item->list_of_items_id)
                            ->first();

                        if (is_null($check_storage)) {
                            return response()->json([
                                'message' => 'The data was invalid.',
                                'errors' => ['Data jumlah barang tidak ditemukan!'],
                            ], 404);
                        }

                        $check_storage_name = DB::table('list_of_items')
                            ->select('item_name')
                            ->where('id', '=', $check_price_item->list_of_items_id)
                            ->first();

                        if (is_null($check_storage_name)) {
                            return response()->json([
                                'message' => 'The data was invalid.',
                                'errors' => ['Data jumlah barang tidak ditemukan!'],
                            ], 404);
                        }

                        if ($value_item['quantity'] > $check_storage->total_item) {

                            return response()->json([
                                'message' => 'The given data was invalid.',
                                'errors' => ['Jumlah stok ' . $check_storage_name->item_name . ' kurang atau habis!'],
                            ], 422);
                        }

                        if ($value_item['quantity'] <= 0) {
                            return response()->json([
                                'message' => 'The given data was invalid.',
                                'errors' => ['Jumlah barang ' . $check_storage_name->item_name . ' belum diisi!'],
                            ], 422);
                        }
                    } else {

                        $detail_item = DetailItemPatient::find($value_item['id']);
                        //kalau data yang sudah pernah ada
                        if (is_null($detail_item)) {
                            return response()->json([
                                'message' => 'The data was invalid.',
                                'errors' => ['Data Daftar Barang Pasien tidak ditemukan!'],
                            ], 404);
                        }
                        //untuk mendapatkan data stok terupdate
                        $check_price_item = DB::table('price_items')
                            ->select('list_of_items_id')
                            ->where('id', '=', $value_item['price_item_id'])
                            ->first();

                        if (is_null($check_price_item)) {
                            return response()->json([
                                'message' => 'The data was invalid.',
                                'errors' => ['Data Price Item not found!'],
                            ], 404);
                        }

                        $check_stock = DB::table('list_of_items')
                            ->select('total_item')
                            ->where('id', '=', $check_price_item->list_of_items_id)
                            ->first();

                        if (is_null($check_stock)) {
                            return response()->json([
                                'message' => 'The data was invalid.',
                                'errors' => ['Data Daftar Barang tidak ditemukan!'],
                            ], 404);
                        }

                        $check_storage_name = DB::table('list_of_items')
                            ->select('item_name')
                            ->where('id', '=', $check_price_item->list_of_items_id)
                            ->first();

                        if (is_null($check_storage_name)) {
                            return response()->json([
                                'message' => 'The data was invalid.',
                                'errors' => ['Data jumlah barang tidak ditemukan!'],
                            ], 404);
                        }

                        $check_item_result = DB::table('detail_item_patients')
                            ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                            ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                            ->select('detail_item_patients.quantity as quantity')
                            ->where('list_of_items.id', '=', $check_price_item->list_of_items_id)
                            ->where('price_items.id', '=', $value_item['price_item_id'])
                            ->first();

                        if (is_null($check_item_result)) {
                            return response()->json([
                                'message' => 'The data was invalid.',
                                'errors' => ['Data Hasil Pemeriksaan tidak ditemukan!'],
                            ], 404);
                        }

                        //validasi kalau data input lebih dari data awal
                        if ($value_item['quantity'] > $check_item_result->quantity) {

                            $res_value_item = $value_item['quantity'] - $check_item_result->quantity;

                            if ($res_value_item > $check_stock->total_item) {

                                return response()->json([
                                    'message' => 'The given data was invalid.',
                                    'errors' => ['Jumlah stok ' . $check_storage_name->item_name . ' kurang atau habis!'],
                                ], 422);
                            }

                            $check_price_item = DB::table('price_items')
                                ->select('list_of_items_id')
                                ->where('id', '=', $value_item['price_item_id'])
                                ->first();

                            if (is_null($check_price_item)) {
                                return response()->json([
                                    'message' => 'The data was invalid.',
                                    'errors' => ['Data Harga Barang tidak ditemukan!'],
                                ], 404);
                            }

                            $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                            if (is_null($list_of_items)) {
                                return response()->json([
                                    'message' => 'The data was invalid.',
                                    'errors' => ['Data Daftar Barang tidak ditemukan!'],
                                ], 404);
                            }

                            $detail_item_patient = DetailItemPatient::find($value_item['id']);

                            if (is_null($detail_item_patient)) {

                                return response()->json([
                                    'message' => 'The data was invalid.',
                                    'errors' => ['Data tidak ditemukan!'],
                                ], 404);
                            }
                        } elseif ($value_item['quantity'] < $check_item_result->quantity) {

                            $res_value_item = $check_item_result->quantity - $value_item['quantity'];

                            $check_price_item = DB::table('price_items')
                                ->select('list_of_items_id')
                                ->where('id', '=', $value_item['price_item_id'])
                                ->first();

                            if (is_null($check_price_item)) {
                                return response()->json([
                                    'message' => 'The data was invalid.',
                                    'errors' => ['Data Harga Barang tidak ditemukan!'],
                                ], 404);
                            }

                            $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                            if (is_null($list_of_items)) {
                                return response()->json([
                                    'message' => 'The data was invalid.',
                                    'errors' => ['Data tidak ditemukan!'],
                                ], 404);
                            }

                            $detail_item_patient = DetailItemPatient::find($value_item['id']);

                            if (is_null($detail_item_patient)) {

                                return response()->json([
                                    'message' => 'The data was invalid.',
                                    'errors' => ['Data tidak ditemukan!'],
                                ], 404);
                            }
                        } else {

                            $check_price_item = DB::table('price_items')
                                ->select('list_of_items_id')
                                ->where('id', '=', $value_item['price_item_id'])
                                ->first();

                            if (is_null($check_price_item)) {
                                return response()->json([
                                    'message' => 'The data was invalid.',
                                    'errors' => ['Data Harga Barang tidak ditemukan!'],
                                ], 404);
                            }

                            $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                            if (is_null($list_of_items)) {
                                return response()->json([
                                    'message' => 'The data was invalid.',
                                    'errors' => ['Data tidak ditemukan!'],
                                ], 404);
                            }

                            $detail_item_patient = DetailItemPatient::find($value_item['id']);

                            if (is_null($detail_item_patient)) {

                                return response()->json([
                                    'message' => 'The data was invalid.',
                                    'errors' => ['Data tidak ditemukan!'],
                                ], 404);
                            }
                        }
                    }
                }
            }

            foreach ($result_item as $value_data) {

                if ($value_data['id']) {

                    foreach ($value_data['list_of_medicine'] as $value_item) {

                        if (is_null($value_item['status'])) {

                            $check_temp_count_items = DB::table('temp_count_items')
                                ->where('price_item_id', '=', $value_item['price_item_id'])
                                ->where('user_id', '=', $request->user()->id)
                                ->sum('temp_count_items.quantity');

                            $check_temp_item = DB::table('temp_count_items')
                                ->where('price_item_id', '=', $value_item['price_item_id'])
                                ->where('user_id', '=', $request->user()->id)
                                ->first();

                            if (is_null($check_temp_item)) {

                                $service_list = TempCountItem::create([
                                    'price_item_id' => $value_item['price_item_id'],
                                    'quantity' => $value_item['quantity'],
                                    'user_id' => $request->user()->id,
                                ]);
                            } else {

                                $adding_value = $check_temp_count_items;

                                $find_id = DB::table('temp_count_items')
                                    ->select('id', 'quantity')
                                    ->where('price_item_id', '=', $value_item['price_item_id'])
                                    ->where('user_id', '=', $request->user()->id)
                                    ->first();

                                $res_adding = $adding_value + $value_item['quantity'];

                                $find_price_item = TempCountItem::find($find_id->id);
                                $find_price_item->quantity = $res_adding;
                                $find_price_item->save();
                            }
                        }
                    }
                }
            }

            $find_temp = TempCountItem::where('user_id', '=', $request->user()->id)->get();

            $data_item = [];

            $data_item = $find_temp;

            foreach ($data_item as $find_stock) {

                $check_price_item = DB::table('price_items')
                    ->select('list_of_items_id')
                    ->where('id', '=', $find_stock['price_item_id'])
                    ->first();

                $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                $count_item = $list_of_items->total_item - $find_stock['quantity'];

                if ($count_item < 0) {

                    $detail_item = DB::table('temp_count_items')
                        ->where('user_id', $request->user()->id)->delete();

                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['Jumlah stok ' . $list_of_items->item_name . ' kurang atau habis!'],
                    ], 422);
                }
            }
        }

        //update hasil pemeriksaan

        $check_up_result = CheckUpResult::find($request->id);

        if (is_null($check_up_result)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data Hasil Pemeriksaan tidak ditemukan!'],
            ], 404);
        }

        $anamnesa = "";
        if (!is_null($request->anamnesa)) {
            $anamnesa = $request->anamnesa;
        }

        $sign = "";
        if (!is_null($request->sign)) {
            $sign = $request->sign;
        }

        $diagnosa = "";
        if (!is_null($request->diagnosa)) {
            $diagnosa = $request->diagnosa;
        }

        $check_up_result->patient_registration_id = $request->patient_registration_id;
        $check_up_result->anamnesa = $anamnesa;
        $check_up_result->sign = $sign;
        $check_up_result->diagnosa = $diagnosa;
        $check_up_result->status_outpatient_inpatient = $request->status_outpatient_inpatient;
        $check_up_result->status_finish = $check_up_result->status_finish == 1 ? 1 : ($request->status_finish ?? 0);
        $check_up_result->status_pengabaran = $request->status_pengabaran;
        $check_up_result->alasan_tidak_pengabaran = $request->status_pengabaran == 0 ? ($request->alasan_tidak_pengabaran ?? '') : null;
        $check_up_result->user_update_id = $request->user()->id;
        $check_up_result->updated_at = \Carbon\Carbon::now();
        $check_up_result->save();

        // Batalkan follow-up pending jika status pengabaran diubah ke tidak
        if (!$request->status_pengabaran) {
            CheckUpFollowUp::where('check_up_result_id', $check_up_result->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        }

        //update jasa

        foreach ($services as $key_service) {

            if (is_null($key_service['id'])) {

                $service_list = DetailServicePatient::create([
                    'check_up_result_id' => $check_up_result->id,
                    'price_service_id' => $key_service['price_service_id'],
                    'quantity' => $key_service['quantity'],
                    'price_overall' => $key_service['price_overall'],
                    'status_paid_off' => 0,
                    'user_id' => $request->user()->id,
                ]);
            } elseif ($key_service['status'] == 'del' || $key_service['quantity'] == 0) {

                if (!is_null($key_service['id'])) {

                    $detail_service_patient = DetailServicePatient::find($key_service['id']);
                    $detail_service_patient->delete();
                }
            } else {

                $detail_service_patient = DetailServicePatient::find($key_service['id']);

                $detail_service_patient->check_up_result_id = $check_up_result->id;
                $detail_service_patient->price_service_id = $key_service['price_service_id'];
                $detail_service_patient->quantity = $key_service['quantity'];
                $detail_service_patient->price_overall = $key_service['price_overall'];
                $detail_service_patient->user_update_id = $request->user()->id;
                $detail_service_patient->updated_at = \Carbon\Carbon::now();
                $detail_service_patient->save();
            }
        }

        //update barang
        if ($request->item) {

            $temp_item = $request->item;

            $result_item = json_decode(json_encode($temp_item), true);

            foreach ($result_item as $res_group) {
                //jika kelompok obat terdapat status delete maka kelompok obat dan semua obat akan dihapus
                if ($res_group['status'] == 'del' && $res_group['id']) {

                    $parent = DB::table('detail_medicine_group_check_up_results')
                        ->where('id', '=', $res_group['id'])
                        ->first();

                    $find_child = DB::table('detail_item_patients')
                        ->where('detail_medicine_group_id', '=', $parent->id)
                        ->get();

                    foreach ($find_child as $res_child) {

                        $check_price_item = DB::table('detail_item_patients')
                            ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                            ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                            ->select('list_of_items.id as list_of_items_id')
                            ->where('price_items.id', '=', $res_child->price_item_id)
                            ->first();

                        $check_item_result = DB::table('detail_item_patients')
                            ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                            ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                            ->select('detail_item_patients.quantity as quantity')
                            ->where('list_of_items.id', '=', $check_price_item->list_of_items_id)
                            ->where('price_items.id', '=', $res_child->price_item_id)
                            ->first();

                        $res_value_item = $check_item_result->quantity;

                        $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                        $count_item = $list_of_items->total_item + $res_value_item;

                        $list_of_items->diff_item = $count_item - $list_of_items->limit_item;
                        $list_of_items->total_item = $count_item;
                        $list_of_items->user_update_id = $request->user()->id;
                        $list_of_items->updated_at = \Carbon\Carbon::now();
                        $list_of_items->save();

                        $item_history = HistoryItemMovement::create([
                            'price_item_id' => $res_child->price_item_id,
                            'quantity' => $res_value_item,
                            'status' => 'tambah',
                            'user_id' => $request->user()->id,
                        ]);

                        $detail_item = DB::table('detail_item_patients')
                            ->where('id', $res_child->id)->delete();
                    }

                    $detail_medicine_group = DB::table('detail_medicine_group_check_up_results')
                        ->where('id', $res_group['id'])->delete();
                } else if (is_null($res_group['status']) && $res_group['id']) {
                    $detail_medicine_group = Detail_medicine_group_check_up_result::find($res_group['id']);

                    $detail_medicine_group->medicine_group_id = $res_group['medicine_group_id'];
                    $detail_medicine_group->quantity = $res_group['quantity'];
                    $detail_medicine_group->remark = $res_group['remark'];
                    $detail_medicine_group->user_update_id = $request->user()->id;
                    $detail_medicine_group->updated_at = \Carbon\Carbon::now();
                    $detail_medicine_group->save();

                    foreach ($res_group['list_of_medicine'] as $value_item) {

                        if (is_null($value_item['id'])) {

                            $item_list = DetailItemPatient::create([
                                'check_up_result_id' => $check_up_result->id,
                                'price_item_id' => $value_item['price_item_id'],
                                'detail_medicine_group_id' => $res_group['id'],
                                'quantity' => $value_item['quantity'],
                                'price_overall' => $value_item['price_overall'],
                                'user_id' => $request->user()->id,
                            ]);

                            $check_price_item = DB::table('detail_item_patients')
                                ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                                ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                                ->select('list_of_items.id as list_of_items_id')
                                ->where('price_items.id', '=', $value_item['price_item_id'])
                                ->first();

                            $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                            $count_item = $list_of_items->total_item - $value_item['quantity'];

                            $list_of_items->diff_item = $count_item - $list_of_items->limit_item;
                            $list_of_items->total_item = $count_item;
                            $list_of_items->user_update_id = $request->user()->id;
                            $list_of_items->updated_at = \Carbon\Carbon::now();
                            $list_of_items->save();

                            $item_history = HistoryItemMovement::create([
                                'price_item_id' => $value_item['price_item_id'],
                                'quantity' => $value_item['quantity'],
                                'status' => 'kurang',
                                'user_id' => $request->user()->id,
                            ]);
                        } elseif ($value_item['status'] == 'del' || $value_item['quantity'] == 0) {

                            $check_price_item = DB::table('detail_item_patients')
                                ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                                ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                                ->select('list_of_items.id as list_of_items_id')
                                ->where('price_items.id', '=', $value_item['price_item_id'])
                                ->first();

                            $check_item_result = DB::table('detail_item_patients')
                                ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                                ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                                ->select('detail_item_patients.quantity as quantity')
                                ->where('list_of_items.id', '=', $check_price_item->list_of_items_id)
                                ->where('price_items.id', '=', $value_item['price_item_id'])
                                ->first();

                            $res_value_item = $check_item_result->quantity;

                            $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                            $count_item = $list_of_items->total_item + $res_value_item;

                            $list_of_items->diff_item = $count_item - $list_of_items->limit_item;
                            $list_of_items->total_item = $count_item;
                            $list_of_items->user_update_id = $request->user()->id;
                            $list_of_items->updated_at = \Carbon\Carbon::now();
                            $list_of_items->save();

                            $item_history = HistoryItemMovement::create([
                                'price_item_id' => $value_item['price_item_id'],
                                'quantity' => $res_value_item,
                                'status' => 'tambah',
                                'user_id' => $request->user()->id,
                            ]);

                            $detail_item = DB::table('detail_item_patients')
                                ->where('id', $value_item['id'])->delete();
                        } else {

                            //untuk cek quantity yang sudah ada untuk mencari selisih penambahan
                            $check_item_result = DB::table('detail_item_patients')
                                ->select('quantity')
                                ->where('check_up_result_id', '=', $request->id)
                                ->where('price_item_id', '=', $value_item['price_item_id'])
                                ->first();

                            if ($value_item['quantity'] > $check_item_result->quantity) {

                                $res_value_item = $value_item['quantity'] - $check_item_result->quantity;

                                $check_price_item = DB::table('price_items')
                                    ->select('list_of_items_id')
                                    ->where('id', '=', $value_item['price_item_id'])
                                    ->first();

                                $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                                $count_item = $list_of_items->total_item - $res_value_item;

                                $list_of_items->diff_item = $count_item - $list_of_items->limit_item;
                                $list_of_items->total_item = $count_item;
                                $list_of_items->user_update_id = $request->user()->id;
                                $list_of_items->updated_at = \Carbon\Carbon::now();
                                $list_of_items->save();

                                $detail_item_patient = DetailItemPatient::find($value_item['id']);

                                $detail_item_patient->price_item_id = $value_item['price_item_id'];
                                $detail_item_patient->quantity = $value_item['quantity'];
                                $detail_item_patient->price_overall = $value_item['price_overall'];
                                $detail_item_patient->user_update_id = $request->user()->id;
                                $detail_item_patient->updated_at = \Carbon\Carbon::now();
                                $detail_item_patient->detail_medicine_group_id = $res_group['id'];
                                $detail_item_patient->save();

                                $item_history = HistoryItemMovement::create([
                                    'price_item_id' => $value_item['price_item_id'],
                                    'quantity' => $res_value_item,
                                    'status' => 'kurang',
                                    'user_id' => $request->user()->id,
                                ]);
                            } elseif ($value_item['quantity'] < $check_item_result->quantity) {

                                $res_value_item = $check_item_result->quantity - $value_item['quantity'];

                                $check_price_item = DB::table('price_items')
                                    ->select('list_of_items_id')
                                    ->where('id', '=', $value_item['price_item_id'])
                                    ->first();

                                $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                                $count_item = $list_of_items->total_item + $res_value_item;

                                $list_of_items->diff_item = $count_item - $list_of_items->limit_item;
                                $list_of_items->total_item = $count_item;
                                $list_of_items->user_update_id = $request->user()->id;
                                $list_of_items->updated_at = \Carbon\Carbon::now();
                                $list_of_items->save();

                                $detail_item_patient = DetailItemPatient::find($value_item['id']);

                                $detail_item_patient->price_item_id = $value_item['price_item_id'];
                                $detail_item_patient->quantity = $value_item['quantity'];
                                $detail_item_patient->price_overall = $value_item['price_overall'];
                                $detail_item_patient->user_update_id = $request->user()->id;
                                $detail_item_patient->updated_at = \Carbon\Carbon::now();
                                $detail_item_patient->detail_medicine_group_id = $res_group['id'];
                                $detail_item_patient->save();

                                $item_history = HistoryItemMovement::create([
                                    'price_item_id' => $value_item['price_item_id'],
                                    'quantity' => $res_value_item,
                                    'status' => 'tambah',
                                    'user_id' => $request->user()->id,
                                ]);
                            } else {

                                $detail_item_patient = DetailItemPatient::find($value_item['id']);

                                $detail_item_patient->price_item_id = $value_item['price_item_id'];
                                $detail_item_patient->quantity = $value_item['quantity'];
                                $detail_item_patient->price_overall = $value_item['price_overall'];
                                $detail_item_patient->user_update_id = $request->user()->id;
                                $detail_item_patient->updated_at = \Carbon\Carbon::now();
                                $detail_item_patient->detail_medicine_group_id = $res_group['id'];
                                $detail_item_patient->save();
                            }
                        }
                    }
                } elseif (is_null($res_group['status']) && is_null($res_group['id'])) {
                    $add_parent = Detail_medicine_group_check_up_result::create([
                        'check_up_result_id' => $check_up_result->id,
                        'medicine_group_id' => $res_group['medicine_group_id'],
                        'quantity' => $res_group['quantity'],
                        'status_paid_off' => 0,
                        'user_id' => $request->user()->id,
                    ]);

                    $this->update_item($res_group['list_of_medicine'], $add_parent, $check_up_result, $request);
                }
            }
        }

        if ($request->status_outpatient_inpatient == true && $request->inpatient != "") {

            $inpatient = InPatient::create([
                'check_up_result_id' => $request->id,
                'description' => $request->inpatient,
                'user_id' => $request->user()->id,
            ]);
        }

        $detail_item = DB::table('temp_count_items')
            ->where('user_id', $request->user()->id)->delete();

        return response()->json(
            [
                'message' => 'Ubah Data Berhasil!',
            ],
            200
        );
    }

    private function update_item(array $list_of_medicine, Detail_medicine_group_check_up_result $add_parent, CheckUpResult $check_up_result, Request $request)
    {
        foreach ($list_of_medicine as $value_item) {

            if (is_null($value_item['id'])) {

                $item_list = DetailItemPatient::create([
                    'check_up_result_id' => $check_up_result->id,
                    'price_item_id' => $value_item['price_item_id'],
                    'detail_medicine_group_id' => $add_parent->id,
                    'quantity' => $value_item['quantity'],
                    'price_overall' => $value_item['price_overall'],
                    'user_id' => $request->user()->id,
                ]);

                $check_price_item = DB::table('detail_item_patients')
                    ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                    ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                    ->select('list_of_items.id as list_of_items_id')
                    ->where('price_items.id', '=', $value_item['price_item_id'])
                    ->first();

                $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                $count_item = $list_of_items->total_item - $value_item['quantity'];

                $list_of_items->diff_item = $count_item - $list_of_items->limit_item;
                $list_of_items->total_item = $count_item;
                $list_of_items->user_update_id = $request->user()->id;
                $list_of_items->updated_at = \Carbon\Carbon::now();
                $list_of_items->save();

                $item_history = HistoryItemMovement::create([
                    'price_item_id' => $value_item['price_item_id'],
                    'quantity' => $value_item['quantity'],
                    'status' => 'kurang',
                    'user_id' => $request->user()->id,
                ]);
            } elseif ($value_item['status'] == 'del' || $value_item['quantity'] == 0) {

                $check_price_item = DB::table('detail_item_patients')
                    ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                    ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                    ->select('list_of_items.id as list_of_items_id')
                    ->where('price_items.id', '=', $value_item['price_item_id'])
                    ->first();

                $check_item_result = DB::table('detail_item_patients')
                    ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                    ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                    ->select('detail_item_patients.quantity as quantity')
                    ->where('list_of_items.id', '=', $check_price_item->list_of_items_id)
                    ->where('price_items.id', '=', $value_item['price_item_id'])
                    ->first();

                $res_value_item = $check_item_result->quantity;

                $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                $count_item = $list_of_items->total_item + $res_value_item;

                $list_of_items->diff_item = $count_item - $list_of_items->limit_item;
                $list_of_items->total_item = $count_item;
                $list_of_items->user_update_id = $request->user()->id;
                $list_of_items->updated_at = \Carbon\Carbon::now();
                $list_of_items->save();

                $item_history = HistoryItemMovement::create([
                    'price_item_id' => $value_item['price_item_id'],
                    'quantity' => $res_value_item,
                    'status' => 'tambah',
                    'user_id' => $request->user()->id,
                ]);

                $detail_item = DB::table('detail_item_patients')
                    ->where('id', $value_item['id'])->delete();
            } else {

                //untuk cek quantity yang sudah ada untuk mencari selisih penambahan
                $check_item_result = DB::table('detail_item_patients')
                    ->select('quantity')
                    ->where('check_up_result_id', '=', $request->id)
                    ->where('price_item_id', '=', $value_item['price_item_id'])
                    ->first();

                if ($value_item['quantity'] > $check_item_result->quantity) {

                    $res_value_item = $value_item['quantity'] - $check_item_result->quantity;

                    $check_price_item = DB::table('price_items')
                        ->select('list_of_items_id')
                        ->where('id', '=', $value_item['price_item_id'])
                        ->first();

                    $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                    $count_item = $list_of_items->total_item - $res_value_item;

                    $list_of_items->diff_item = $count_item - $list_of_items->limit_item;
                    $list_of_items->total_item = $count_item;
                    $list_of_items->user_update_id = $request->user()->id;
                    $list_of_items->updated_at = \Carbon\Carbon::now();
                    $list_of_items->save();

                    $detail_item_patient = DetailItemPatient::find($value_item['id']);

                    $detail_item_patient->price_item_id = $value_item['price_item_id'];
                    $detail_item_patient->quantity = $value_item['quantity'];
                    $detail_item_patient->price_overall = $value_item['price_overall'];
                    $detail_item_patient->user_update_id = $request->user()->id;
                    $detail_item_patient->updated_at = \Carbon\Carbon::now();
                    $detail_item_patient->detail_medicine_group_id = $add_parent->id;
                    $detail_item_patient->save();

                    $item_history = HistoryItemMovement::create([
                        'price_item_id' => $value_item['price_item_id'],
                        'quantity' => $res_value_item,
                        'status' => 'kurang',
                        'user_id' => $request->user()->id,
                    ]);
                } elseif ($value_item['quantity'] < $check_item_result->quantity) {

                    $res_value_item = $check_item_result->quantity - $value_item['quantity'];

                    $check_price_item = DB::table('price_items')
                        ->select('list_of_items_id')
                        ->where('id', '=', $value_item['price_item_id'])
                        ->first();

                    $list_of_items = ListofItems::find($check_price_item->list_of_items_id);

                    $count_item = $list_of_items->total_item + $res_value_item;

                    $list_of_items->diff_item = $count_item - $list_of_items->limit_item;
                    $list_of_items->total_item = $count_item;
                    $list_of_items->user_update_id = $request->user()->id;
                    $list_of_items->updated_at = \Carbon\Carbon::now();
                    $list_of_items->save();

                    $detail_item_patient = DetailItemPatient::find($value_item['id']);

                    $detail_item_patient->price_item_id = $value_item['price_item_id'];
                    $detail_item_patient->quantity = $value_item['quantity'];
                    $detail_item_patient->price_overall = $value_item['price_overall'];
                    $detail_item_patient->user_update_id = $request->user()->id;
                    $detail_item_patient->updated_at = \Carbon\Carbon::now();
                    $detail_item_patient->detail_medicine_group_id = $add_parent->id;
                    $detail_item_patient->save();

                    $item_history = HistoryItemMovement::create([
                        'price_item_id' => $value_item['price_item_id'],
                        'quantity' => $res_value_item,
                        'status' => 'tambah',
                        'user_id' => $request->user()->id,
                    ]);
                } else {

                    $detail_item_patient = DetailItemPatient::find($value_item['id']);

                    $detail_item_patient->price_item_id = $value_item['price_item_id'];
                    $detail_item_patient->quantity = $value_item['quantity'];
                    $detail_item_patient->price_overall = $value_item['price_overall'];
                    $detail_item_patient->user_update_id = $request->user()->id;
                    $detail_item_patient->updated_at = \Carbon\Carbon::now();
                    $detail_item_patient->detail_medicine_group_id = $add_parent->id;
                    $detail_item_patient->save();
                }
            }
        }
    }

    public function delete(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'The user role was invalid.',
                'errors' => ['Hanya admin yang dapat menghapus data hasil pemeriksaan!'],
            ], 403);
        }

        $check_up_result = CheckUpResult::find($request->id);

        if (is_null($check_up_result)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data Hasil Pemeriksaan tidak ditemukan!'],
            ], 404);
        }

        $medicine_group = Detail_medicine_group_check_up_result::where('check_up_result_id', '=', $request->id)->get();

        if (is_null($medicine_group)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data Kelompok Obat tidak ditemukan!'],
            ], 404);
        }

        $data_medicine_group = [];

        $data_medicine_group = $medicine_group;

        foreach ($data_medicine_group as $resdata) {

            $detail_item = DetailItemPatient::where('detail_medicine_group_id', '=', $resdata->id)->get();

            if ($detail_item) {

                $data_item = [];

                $data_item = $detail_item;

                foreach ($data_item as $datas) {

                    $check_price_item = DB::table('price_items')
                        ->select('list_of_items_id')
                        ->where('id', '=', $datas->price_item_id)
                        ->first();

                    if (is_null($check_price_item)) {
                        return response()->json([
                            'message' => 'The data was invalid.',
                            'errors' => ['Data Harga Barang tidak ditemukan!'],
                        ], 404);
                    }

                    $check_list_of_item = DB::table('list_of_items')
                        ->where('id', '=', $check_price_item->list_of_items_id)
                        ->first();

                    if (is_null($check_list_of_item)) {
                        return response()->json([
                            'message' => 'The data was invalid.',
                            'errors' => ['Data Daftar Barang Pasien tidak ditemukan!'],
                        ], 404);
                    }

                    $find_prev_stock = DB::table('detail_item_patients')
                        ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                        ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                        ->select('detail_item_patients.quantity as quantity')
                        ->where('list_of_items.id', '=', $check_list_of_item->id)
                        ->where('price_items.id', '=', $datas->price_item_id)
                        ->where('detail_item_patients.detail_medicine_group_id', '=', $resdata->id)
                        ->first();

                    $res_total_item = $check_list_of_item->total_item + $find_prev_stock->quantity;

                    $list_of_items = ListofItems::find($check_price_item->list_of_items_id);
                    $list_of_items->diff_item = $res_total_item - $list_of_items->limit_item;
                    $list_of_items->total_item = $res_total_item;
                    $list_of_items->user_update_id = $request->user()->id;
                    $list_of_items->updated_at = \Carbon\Carbon::now();
                    $list_of_items->save();

                    $item_history = HistoryItemMovement::create([
                        'price_item_id' => $datas->price_item_id,
                        'quantity' => $find_prev_stock->quantity,
                        'status' => 'tambah',
                        'user_id' => $request->user()->id,
                    ]);

                    $values = DetailItemPatient::where('id', '=', $datas->id)
                        ->delete();
                }
            }

            $val_medicine_group = Detail_medicine_group_check_up_result::where('id', '=', $resdata->id)
                ->delete();
        }

        $detail_service = DetailServicePatient::where('check_up_result_id', '=', $request->id)->get();

        if (is_null($detail_service)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data Jasa Pasien tidak ditemukan!'],
            ], 404);
        }

        $delete_detail_service_patients = DetailServicePatient::where('check_up_result_id', '=', $request->id)
            ->delete();

        $inpatient = InPatient::where('check_up_result_id', '=', $request->id)->get();

        if (is_null($inpatient)) {
            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data Rawat Inap tidak ditemukan!'],
            ], 404);
        }

        $delete_inpatient = InPatient::where('check_up_result_id', '=', $request->id)
            ->delete();

        $find_images = DB::table('images_check_up_results')
            ->select('images_check_up_results.id', 'images_check_up_results.image')
            ->where('check_up_result_id', '=', $request->id)
            ->get();

        if ($find_images) {

            foreach ($find_images as $image) {

                if (file_exists(public_path() . $image->image)) {

                    File::delete(public_path() . $image->image);

                    $delete = ImagesCheckUpResults::where('id', '=', $image->id)
                        ->delete();
                }
            }
        }

        // if ($check_up_result->status_pengabaran == true) {

            $registration = Registration::find($check_up_result->patient_registration_id);
            $registration->user_update_id = $request->user()->id;
            $registration->acceptance_status = 1;
            $registration->updated_at = \Carbon\Carbon::now();
            $registration->save();
        // }

        $check_up_result = CheckUpResult::find($request->id);
        $check_up_result->delete();

        return response()->json([
            'message' => 'Berhasil menghapus Data',
        ], 200);
    }

    public function upload_images(Request $request)
    {
        // if ($request->user()->role == 'resepsionis') {
        //     return response()->json([
        //         'message' => 'The user role was invalid.',
        //         'errors' => ['Akses User tidak diizinkan!'],
        //     ], 403);

        // }

        $validator = Validator::make($request->all(), [
            'check_up_result_id' => 'required',
            'filenames' => 'required',
            'filenames.*' => 'required|mimes:jpg,png,jpeg',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return response()->json([
                'message' => 'Foto yang dimasukkan tidak valid!',
                'errors' => $errors,
            ], 422);
        }

        if ($request->hasfile('filenames')) {

            $data_item = [];

            $files[] = $request->file('filenames');

            foreach ($files as $file) {

                foreach ($file as $fil) {

                    $file_size = $fil->getSize();

                    $file_size = $file_size / 1024;

                    $oldname = $fil->getClientOriginalName();

                    if ($file_size >= 5) {

                        array_push($data_item, 'Foto ' . $oldname . ' lebih dari 5mb! Harap cek ulang!');
                    }
                }
            }

            if ($data_item) {

                return response()->json([
                    'message' => 'Foto yang dimasukkan tidak valid!',
                    'errors' => $data_item,
                ], 422);
            } else {

                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $name = $fil->hashName();

                        $fil->move(public_path() . '/image_check_up_result/', $name);

                        $fileName = "/image_check_up_result/" . $name;

                        $file = new ImagesCheckUpResults();
                        $file->image = $fileName;
                        $file->check_up_result_id = $request->check_up_result_id;
                        $file->user_id = $request->user()->id;
                        $file->save();
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Berhasil',
        ], 200);
    }

    public function update_upload_images(Request $request)
    {
        // if ($request->user()->role == 'resepsionis') {
        //     return response()->json([
        //         'message' => 'The user role was invalid.',
        //         'errors' => ['Akses User tidak diizinkan!'],
        //     ], 403);

        // }

        $validator = Validator::make($request->all(), [
            'check_up_result_id' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return response()->json([
                'message' => 'Foto yang dimasukkan tidak valid!',
                'errors' => $errors,
            ], 422);
        }

        $image = $request->images;
        $result_image = json_decode($image, true);

        foreach ($result_image as $img) {

            if ($img['status'] == 'del') {

                $find_image = DB::table('images_check_up_results')
                    ->select('images_check_up_results.image')
                    ->where('id', '=', $img['image_id'])
                    ->where('check_up_result_id', '=', $request->check_up_result_id)
                    ->first();

                if ($find_image) {

                    if (file_exists(public_path() . $find_image->image)) {

                        File::delete(public_path() . $find_image->image);

                        $delete = DB::table('images_check_up_results')
                            ->where('id', $img['image_id'])->delete();
                    }
                }
            }
        }

        if ($request->hasfile('filenames')) {

            $data_item = [];

            $files[] = $request->file('filenames');

            foreach ($files as $file) {

                foreach ($file as $fil) {

                    $file_size = $fil->getSize();

                    $file_size = $file_size / 1024;

                    $oldname = $fil->getClientOriginalName();

                    if ($file_size >= 5000) {

                        array_push($data_item, 'Foto ' . $oldname . ' lebih dari 5mb! Harap cek ulang!');
                    }
                }
            }

            if ($data_item) {

                return response()->json([
                    'message' => 'Foto yang dimasukkan tidak valid!',
                    'errors' => $data_item,
                ], 422);
            } else {

                foreach ($files as $file) {

                    foreach ($file as $fil) {

                        $name = $fil->hashName();

                        $fil->move(public_path() . '/image_check_up_result/', $name);

                        $fileName = "/image_check_up_result/" . $name;

                        $file = new ImagesCheckUpResults();
                        $file->image = $fileName;
                        $file->check_up_result_id = $request->check_up_result_id;
                        $file->user_id = $request->user()->id;
                        $file->save();
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Berhasil update',
        ], 200);
    }

    public function payment(Request $request)
    {

        $data = CheckUpResult::find($request->id);

        if (is_null($data)) {

            return response()->json([
                'message' => 'The data was invalid.',
                'errors' => ['Data Hasil Pemeriksaan tidak ditemukan!'],
            ], 404);
        }

        $registration = DB::table('registrations')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('owners', 'patients.owner_id', '=', 'owners.id')
            ->leftJoin('pet_categories', 'patients.pet_category_id', '=', 'pet_categories.id')
            ->leftJoin('complaints', 'registrations.complaint_id', '=', 'complaints.id')
            ->select(
                'registrations.id_number as registration_number',
                'patients.id as patient_id',
                'patients.id_member as patient_number',
                DB::raw('CASE WHEN patients.pet_category_id = 6 AND patients.other_pet_category IS NOT NULL AND patients.other_pet_category != "" THEN patients.other_pet_category ELSE COALESCE(pet_categories.name, patients.pet_category) END as pet_category'),
                'patients.pet_name',
                'patients.pet_gender',
                DB::raw('COALESCE(registrations.pet_year_age, patients.pet_year_age) as pet_year_age'),
                DB::raw('COALESCE(registrations.pet_month_age, patients.pet_month_age) as pet_month_age'),
                DB::raw('COALESCE(registrations.pet_day_age, patients.pet_day_age) as pet_day_age'),
                DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name'),
                DB::raw('(CASE WHEN patients.owner_address = "" THEN owners.owner_address ELSE patients.owner_address END) AS owner_address'),
                DB::raw('(CASE WHEN patients.owner_phone_number = "" THEN owners.owner_phone_number ELSE patients.owner_phone_number END) AS owner_phone_number'),
                DB::raw('CASE WHEN registrations.complaint_id = 11 AND registrations.other_complaint IS NOT NULL THEN registrations.other_complaint ELSE COALESCE(complaints.name, registrations.complaint) END AS complaint'),
                'registrations.registrant'
            )
            ->where('registrations.id', '=', $data->patient_registration_id)
            ->first();

        $data->registration = $registration;

        $user = DB::table('check_up_results')
            ->join('users', 'check_up_results.user_id', '=', 'users.id')
            ->select('users.id as user_id', 'users.username as username', 'users.branch_id')
            ->where('users.id', '=', $data->user_id)
            ->first();

        $data->user = $user;

        $services = DB::table('detail_service_patients')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->join('list_of_services', 'price_services.list_of_services_id', '=', 'list_of_services.id')
            ->join('service_categories', 'list_of_services.service_category_id', '=', 'service_categories.id')
            ->join('users', 'detail_service_patients.user_id', '=', 'users.id')
            ->select(
                'detail_service_patients.id as detail_service_patient_id',
                'price_services.id as price_service_id',
                'list_of_services.id as list_of_service_id',
                'list_of_services.service_name',
                'detail_service_patients.quantity',
                DB::raw("TRIM(detail_service_patients.price_overall)+0 as price_overall"),
                'service_categories.category_name',
                DB::raw("TRIM(price_services.selling_price)+0 as selling_price"),
                'users.fullname as created_by',
                DB::raw("DATE_FORMAT(detail_service_patients.created_at, '%d %b %Y') as created_at")
            )
            ->where('detail_service_patients.check_up_result_id', '=', $data->id)
            ->orderBy('detail_service_patients.id', 'desc')
            ->get();

        $data['services'] = $services;

        $check = DB::table('detail_medicine_group_check_up_results')
            ->select('quantity')
            ->where('check_up_result_id', '=', $data->id)
            ->get();

        $valid = false;

        foreach ($check as $key) {

            if ($key->quantity == 0) {
                $valid = false;
            } else {
                $valid = true;
            }
        }

        if ($valid == true) {

            $item = DB::table('detail_medicine_group_check_up_results as dmg')
                ->join('price_medicine_groups as pmg', 'dmg.medicine_group_id', '=', 'pmg.id')
                ->join('medicine_groups', 'pmg.medicine_group_id', '=', 'medicine_groups.id')
                ->join('users', 'dmg.user_id', '=', 'users.id')
                ->select(
                    'dmg.id as id',
                    'dmg.check_up_result_id',
                    'dmg.medicine_group_id',
                    'medicine_groups.group_name',
                    DB::raw("TRIM(pmg.selling_price)+0 as each_price"),
                    //DB::raw("COUNT(dmg.check_up_result_id) as quantity"),
                    'dmg.quantity as quantity',
                    //DB::raw("TRIM(SUM(pmg.selling_price))+0 as price_overall"),
                    DB::raw("TRIM(pmg.selling_price * dmg.quantity)+0 as price_overall"),
                    'users.fullname as created_by',
                    DB::raw("DATE_FORMAT(dmg.created_at, '%d %b %Y') as created_at")
                )
                ->where('dmg.check_up_result_id', '=', $data->id)
                //->groupby('dmg.medicine_group_id')
                ->orderBy('dmg.id', 'asc')
                ->get();
        } else {
            $item = DB::table('detail_medicine_group_check_up_results as dmg')
                ->join('price_medicine_groups as pmg', 'dmg.medicine_group_id', '=', 'pmg.id')
                ->join('medicine_groups', 'pmg.medicine_group_id', '=', 'medicine_groups.id')
                ->join('users', 'dmg.user_id', '=', 'users.id')
                ->select(
                    'dmg.id as id',
                    'dmg.check_up_result_id',
                    'dmg.medicine_group_id',
                    'medicine_groups.group_name',
                    DB::raw("TRIM(pmg.selling_price)+0 as each_price"),
                    DB::raw("COUNT(dmg.check_up_result_id) as quantity"),
                    DB::raw("TRIM(SUM(pmg.selling_price))+0 as price_overall"),
                    'users.fullname as created_by',
                    DB::raw("DATE_FORMAT(dmg.created_at, '%d %b %Y') as created_at")
                )
                ->where('dmg.check_up_result_id', '=', $data->id)
                ->groupby('dmg.medicine_group_id')
                ->orderBy('dmg.id', 'asc')
                ->get();
        }

        $data['item'] = $item;

        $inpatient = DB::table('in_patients')
            ->join('users', 'in_patients.user_id', '=', 'users.id')
            ->select(
                'in_patients.description',
                DB::raw("DATE_FORMAT(in_patients.created_at, '%d %b %Y') as created_at"),
                'users.fullname as created_by'
            )
            ->where('in_patients.check_up_result_id', '=', $data->id)
            ->get();

        $data['inpatient'] = $inpatient;

        $existing_payment = DB::table('list_of_payments')
            ->where('check_up_result_id', '=', $data->id)
            ->select('id')
            ->first();

        $data['existing_payment_id'] = $existing_payment ? $existing_payment->id : null;

        return response()->json($data, 200);
    }

    public function cetakHasilPemeriksaan(int $id)
    {
        $data = CheckUpResult::find($id);

        if (is_null($data)) {
            abort(404);
        }

        $registration = DB::table('registrations')
            ->join('patients', 'registrations.patient_id', '=', 'patients.id')
            ->join('owners', 'patients.owner_id', '=', 'owners.id')
            ->join('branches', 'patients.branch_id', '=', 'branches.id')
            ->select(
                'registrations.id_number as registration_number',
                'patients.id_member as patient_number',
                'patients.pet_category',
                'patients.pet_name',
                'patients.pet_gender',
                DB::raw('COALESCE(registrations.pet_year_age, patients.pet_year_age) as pet_year_age'),
                DB::raw('COALESCE(registrations.pet_month_age, patients.pet_month_age) as pet_month_age'),
                DB::raw('COALESCE(registrations.pet_day_age, patients.pet_day_age) as pet_day_age'),
                DB::raw('(CASE WHEN patients.owner_name = "" THEN owners.owner_name ELSE patients.owner_name END) AS owner_name'),
                DB::raw('(CASE WHEN patients.owner_address = "" THEN owners.owner_address ELSE patients.owner_address END) AS owner_address'),
                DB::raw('(CASE WHEN patients.owner_phone_number = "" THEN owners.owner_phone_number ELSE patients.owner_phone_number END) AS owner_phone_number'),
                'registrations.complaint',
                'registrations.registrant',
                'branches.branch_name'
            )
            ->where('registrations.id', '=', $data->patient_registration_id)
            ->first();

        $user = DB::table('check_up_results')
            ->join('users', 'check_up_results.user_id', '=', 'users.id')
            ->select('users.id as user_id', 'users.username as username', 'users.fullname as fullname')
            ->where('check_up_results.id', '=', $data->id)
            ->first();

        $services = DB::table('detail_service_patients')
            ->join('price_services', 'detail_service_patients.price_service_id', '=', 'price_services.id')
            ->join('list_of_services', 'price_services.list_of_services_id', '=', 'list_of_services.id')
            ->join('service_categories', 'list_of_services.service_category_id', '=', 'service_categories.id')
            ->select(
                'list_of_services.service_name',
                'detail_service_patients.quantity',
                DB::raw("TRIM(detail_service_patients.price_overall)+0 as price_overall"),
                'service_categories.category_name',
                DB::raw("TRIM(price_services.selling_price)+0 as selling_price")
            )
            ->where('detail_service_patients.check_up_result_id', '=', $data->id)
            ->orderBy('detail_service_patients.id', 'desc')
            ->get();

        $item = DB::table('detail_medicine_group_check_up_results')
            ->join('price_medicine_groups', 'detail_medicine_group_check_up_results.medicine_group_id', '=', 'price_medicine_groups.id')
            ->join('medicine_groups', 'price_medicine_groups.medicine_group_id', '=', 'medicine_groups.id')
            ->select(
                'detail_medicine_group_check_up_results.id as id',
                'detail_medicine_group_check_up_results.quantity as quantity',
                'detail_medicine_group_check_up_results.remark as remark',
                'medicine_groups.group_name'
            )
            ->where('detail_medicine_group_check_up_results.check_up_result_id', '=', $data->id)
            ->get();

        foreach ($item as $value) {
            $detail_item = DB::table('detail_item_patients')
                ->join('price_items', 'detail_item_patients.price_item_id', '=', 'price_items.id')
                ->join('list_of_items', 'price_items.list_of_items_id', '=', 'list_of_items.id')
                ->join('category_item', 'list_of_items.category_item_id', '=', 'category_item.id')
                ->join('unit_item', 'list_of_items.unit_item_id', '=', 'unit_item.id')
                ->join('users', 'detail_item_patients.user_id', '=', 'users.id')
                ->select(
                    'list_of_items.item_name',
                    'detail_item_patients.quantity',
                    'unit_item.unit_name',
                    'category_item.category_name',
                    'users.fullname as created_by',
                    DB::raw("DATE_FORMAT(detail_item_patients.created_at, '%d %b %Y') as created_at")
                )
                ->where('detail_item_patients.detail_medicine_group_id', '=', $value->id)
                ->orderBy('detail_item_patients.id', 'asc')
                ->get();

            $value->list_of_medicine = $detail_item;
        }

        $inpatient = DB::table('in_patients')
            ->join('users', 'in_patients.user_id', '=', 'users.id')
            ->select(
                'in_patients.description',
                DB::raw("DATE_FORMAT(in_patients.created_at, '%d %b %Y') as created_at"),
                'users.fullname as created_by'
            )
            ->where('in_patients.check_up_result_id', '=', $data->id)
            ->get();

        $filename = 'Hasil_Pemeriksaan_' . ($registration->registration_number ?? $id) . '.pdf';

        return \PDF::loadView('hasil-pemeriksaan-print', compact('data', 'registration', 'user', 'services', 'item', 'inpatient'))->download($filename);
    }
}
