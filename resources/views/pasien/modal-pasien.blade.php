<div class="modal fade" id="modal-pasien">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title"></h4>
      </div>
      <div class="modal-body">
        <form>
          <div class="box-body">
            <table class="detail-register">
              <tr>
                <td class="detail-label">No Registrasi</td>
                <td id="noRegisTxt" class="detail-value"></td>
              </tr>
            </table>
            <div class="form-group field-cabang">
              <label for="branch">Cabang</label>
              <select id="branch" class="form-control" style="width: 100%">
              </select>
              <div id="branchErr1" class="validate-error"></div>
            </div>
            <div class="form-group">
              <label for="animalType">Jenis Hewan</label>
              <select id="animalType" class="form-control" style="width: 100%">
                <option value="">Pilih Jenis Hewan</option>
              </select>
              <input id="otherAnimalType" type="text" class="form-control" placeholder="Masukan Jenis Hewan Lainnya" style="display:none; margin-top:8px;">
              <div id="animalTypeErr1" class="validate-error"></div>
            </div>
            <div class="form-group">
              <label for="namaLengkap">Nama Hewan</label>
              <input id="animalName" type="text" class="form-control" placeholder="Masukan Nama Hewan">
              <div id="animalNameErr1" class="validate-error"></div>
            </div>
            <div class="form-group">
              <label for="animalSex">Jenis Kelamin</label>
              <select id="animalSex" class="form-control" style="width: 100%">
                <option selected="selected" value="">Pilih jenis kelamin</option>
                <option value="jantan">Jantan</option>
                <option value="betina">Betina</option>
                <option value="tidak diketahui">Tidak Diketahui</option>
              </select>
              <div id="animalSexErr1" class="validate-error"></div>
            </div>
            <div class="form-group">
              <label for="animalAge">Usia Hewan</label>
              <div class="section-age-animal">
                <input id="animalAgeYear" type="number" class="form-control" placeholder="Masukan tahun hewan" min="0">
                <input id="animalAgeMonth" type="number" class="form-control" placeholder="Masukan bulan hewan" min="0">
                <input id="animalAgeDay" type="number" class="form-control" placeholder="Masukan hari hewan" min="0">
              </div>
              <div id="animalAgeErr1" class="validate-error"></div>
            </div>
            <div class="form-group isShowPemilik">
              <label for="ownerName">Nama Pemilik</label>
              <div id="section-nama-pemilik">
                <select id="ownerDropdown" name="ownerDropdown" style="width: 75%;"></select>
                <input id="ownerName" type="text" class="form-control" placeholder="Masukan Nama Pemilik" style="display: none; width: 75%">
                <button type="button" class="btn btn-primary" id="btnNamaPemilik">Tambah Pemilik</button>
              </div>
              <div id="ownerNameErr1" class="validate-error"></div>
            </div>
            <div class="form-group isShowPemilik">
              <label for="ownerAddress">Alamat Pemilik</label>
              <input id="ownerAddress" type="text" class="form-control" placeholder="Masukan Alamat Pemilik">
              <div id="ownerAddressErr1" class="validate-error"></div>
            </div>
            <div class="form-group isShowPemilik">
              <label for="ownerTelp">Nomor HP Pemilik</label>
              <input id="ownerTelp" type="number" class="form-control" placeholder="Masukan Nomor HP Pemilik">
              <div id="ownerTelpErr1" class="validate-error"></div>
            </div>
            <div class="form-group">
              <div id="beErr" class="validate-error"></div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="btnSubmitPasien">Simpan</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
