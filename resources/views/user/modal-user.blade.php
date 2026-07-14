<div class="modal fade" id="modal-user">
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
              <table class="detail-user">
                <tr>
                  <td class="detail-label">Nomor Kepegawaian</td>
                  <td id="noPegawaiTxt" class="detail-value"></td>
                </tr>
                <tr>
                  <td class="detail-label">Username</td>
                  <td id="usernameTxt" class="detail-value"></td>
                </tr>
                <tr>
                  <td class="detail-label">Email</td>
                  <td id="emailTxt" class="detail-value"></td>
                </tr>
                <tr>
                  <td class="detail-label">Nomor Ponsel</td>
                  <td id="noPonselTxt" class="detail-value"></td>
                </tr>
              </table>
              <div class="form-group stateModalAdd">
                <label for="username">Username</label>
                <input id="username" type="text" class="form-control" placeholder="Masukan Username">
                <div id="usernameErr1" class="validate-error"></div>
              </div>
              <div class="form-group">
                <label for="namaLengkap">Nama Lengkap</label>
                <input id="namaLengkap" type="text" class="form-control" placeholder="Masukan Nama Lengkap">
                <div id="namaLengkapErr1" class="validate-error"></div>
              </div>
              <div class="form-group stateModalAdd">
                <label for="email">Email</label>
                <input id="email" type="text" class="form-control" placeholder="Masukan Alamat Email">
                <div id="emailErr1" class="validate-error"></div>
              </div>
              <div class="form-group stateModalAdd">
                <label for="password">Password</label>
                <div class="p-relative">
                  <input id="password" type="password" class="form-control p-right-42px" placeholder="Masukan Password">
                  <span id="togglePassword" class="glyphicon icon-password glyphicon-eye-open"></span>
                </div>
                <div id="passwordErr1" class="validate-error"></div>
              </div>
              <div class="form-group stateModalAdd">
                <label for="confPassword">Konfirmasi Password</label>
                <div class="p-relative">
                  <input id="confPassword" type="password" class="form-control p-right-42px" placeholder="Masukan Konfirmasi Password">
                  <span id="toggleConfPassword" class="glyphicon icon-password glyphicon-eye-open"></span>
                </div>
                <div id="confPasswordErr1" class="validate-error"></div>
              </div>
              <div class="form-group stateModalAdd">
                <label for="nomPonsel">Nomor Ponsel</label>
                <input id="nomPonsel" type="number" class="form-control" placeholder="Masukan Nomor Ponsel">
                <div id="nomPonselErr1" class="validate-error"></div>
              </div>
              <div class="form-group">
                <label for="role">Role</label>
                <select id="selectRole" class="form-control" style="width: 100%">
                  <option selected="selected" value="">Pilih Role</option>
                  <option value="admin">Admin</option>
                  <option value="resepsionis">Resepsionis</option>
                  <option value="dokter">Dokter</option>
                  <option value="paramedis">Paramedis</option>
                </select>
                <div id="roleErr1" class="validate-error"></div>
              </div>
              <div class="form-group">
                <label for="cabang">Cabang</label>
                <select id="selectCabang" class="form-control" style="width: 100%"></select>
                <div id="cabangErr1" class="validate-error"></div>
              </div>
              <div class="form-group">
                <label for="status">Status</label>
                <select id="selectStatus" class="form-control" style="width: 100%">
                  <option selected="selected" value="">Pilih Status</option>
                  <option value=1>Aktif</option>
                  <option value=0>Non Aktif</option>
                </select>
                <div id="statusErr1" class="validate-error"></div>
              </div>
              <div class="form-group">
                <div id="beErr" class="validate-error"></div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="btnSubmitUser">Simpan</button>
        </div>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>
