<div class="modal fade" id="modal-penggajian">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title"></h4>
			</div>
			<div class="modal-body">
				<form class="form-daftar-barang">
					<div class="box-body">
						<div class="form-group">
							<label for="tanggal">Tanggal</label>
							<div class="input-group date">
                <div class="input-group-addon">
                  <i class="fa fa-calendar"></i>
                </div>
                <input type="text" class="form-control" id="datepicker" placeholder="dd/mm/yyyy" autocomplete="off">
              </div>
							<div id="tanggalErr1" class="validate-error"></div>
						</div>
						<div class="form-group">
							<label for="Nama Karyawan">Nama Karyawan</label>
              <select id="selectedNamaKaryawan" class="form-control" style="width: 100%">
							</select>
							<div id="namaKaryawanErr1" class="validate-error"></div>
						</div>
						<div class="form-group">
							<label for="satuanBarang">Pokok</label>
							<input id="pokok" type="text" class="form-control" min="0" placeholder="Masukan Nominal Gaji Pokok">
							<div id="pokokErr1" class="validate-error"></div>
						</div>
            <div class="form-group">
							<label for="akomodasi">Akomodasi</label>
							<input id="akomodasi" type="text" class="form-control" min="0" placeholder="Masukan Nominal Akomodasi">
							<div id="akomodasiErr1" class="validate-error"></div>
						</div>
            <div class="form-group">
							<label for="fine">Denda</label>
							<input id="fine" type="text" class="form-control" min="0" placeholder="Masukan Nominal Denda">
							<div id="fineErr1" class="validate-error"></div>
						</div>
            <div class="form-group">
							<label for="decrease">Pengurangan Omzet</label>
              <input id="inputMinusTurnover" type="text" class="form-control" min="0" placeholder="Masukan Pengurangan Omzet">
              <div id="minusTurnOverErr1" class="validate-error"></div>
						</div>

            <table class="form-group">
              <tr>
                <td><label for="omzet">Omzet</label></td>
              </tr>
              <tr>
                <td><input id="inputOmset" type="number" class="form-control" min="0" max="100"></td>
                <td class="p-left-15px">%</td>
                <td class="p-left-15px">X</td>
                <td class="p-left-15px"><span id="omset-karyawan"></span></td>
                <td class="p-left-15px">=</td>
                <td class="p-left-15px"><span id="totalOmset"></span></td>
              </tr>
              <tr>
                <td><div id="omsetErr1" class="validate-error"></div></td>
              </tr>
              <tr>
                <td class="p-top-10px"><label for="uangMakan">Uang Makan</label></td>
              </tr>
              <tr>
                <td class="d-flex">
                  <div class="p-top-5px p-right-5px">Rp</div><input id="inputEat" type="number" class="form-control" min="0">
                </td>
                <td></td>
                <td class="p-left-15px">X</td>
                <td class="p-left-15px"><input id="eat-karyawan" type="number" class="form-control" min="0" style="width:80px;"></td>
                <td class="p-left-15px">=</td>
                <td class="p-left-15px"><span id="totalEat"></span></td>
              </tr>
              <tr>
                <td><div id="eatErr1" class="validate-error"></div></td>
              </tr>
              <tr>
                <td class="p-top-10px"><label for="inap">Inap</label></td>
              </tr>
              <tr>
                <td class="d-flex">
                  <div class="p-top-5px p-right-5px">Rp</div><input id="inputInap" type="number" class="form-control" min="0">
                </td>
                <td></td>
                <td class="p-left-15px">X</td>
                <td class="p-left-15px"><span id="inap-karyawan"></span></td>
                <td class="p-left-15px">=</td>
                <td class="p-left-15px"><span id="totalInap"></span></td>
              </tr>
              <tr>
                <td><div id="inapErr1" class="validate-error"></div></td>
              </tr>
              <tr>
                <td class="p-top-10px"><label for="operasi">Operasi</label></td>
              </tr>
              <tr>
                <td><input id="inputOperasi" type="number" class="form-control" min="0" max="100"></td>
                <td class="p-left-15px">%</td>
                <td class="p-left-15px">X</td>
                <td class="p-left-15px"><span id="operasi-karyawan"></span></td>
                <td class="p-left-15px">=</td>
                <td class="p-left-15px"><span id="totalOperasi"></span></td>
              </tr>
              <tr>
                <td><div id="operasiErr1" class="validate-error"></div></td>
              </tr>
              <tr>
                <td class="p-top-10px"><label for="grooming">Grooming</label></td>
              </tr>
              <tr>
                <td class="d-flex">
                  <div class="p-top-5px p-right-5px">Rp</div><input id="inputGrooming" type="number" class="form-control" min="0">
                </td>
                <td></td>
                <td class="p-left-15px">X</td>
                <td class="p-left-15px"><span id="grooming-karyawan"></span></td>
                <td class="p-left-15px">=</td>
                <td class="p-left-15px"><span id="totalGrooming"></span></td>
              </tr>
              <tr>
                <td><div id="groomingErr1" class="validate-error"></div></td>
              </tr>
            </table>

            <div class="form-group d-flex">
							<label for="totalKeseluruhan">Total Keseluruhan</label>
              <span class="m-l-20px" id="totalKeseluruhan"></span>
            </div>

						<div class="form-group">
							<div id="beErr" class="validate-error"></div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" id="btnSubmitPenggajian"></button>
			</div>
		</div>
		<!-- /.modal-content -->
	</div>
	<!-- /.modal-dialog -->
</div>
