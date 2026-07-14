<div class="modal fade" id="modal-reset-password">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title"><i class="fa fa-lock"></i> Reset Password User</h4>
      </div>
      <div class="modal-body">
        <p class="text-muted">Reset password untuk: <strong id="resetPasswordUsername"></strong></p>
        <div class="form-group">
          <label for="resetNewPassword">Password Baru</label>
          <div class="p-relative">
            <input type="password" class="form-control p-right-42px" id="resetNewPassword" placeholder="Min. 8 karakter, huruf besar, kecil, angka, simbol">
            <span id="toggleResetNewPassword" class="glyphicon icon-password glyphicon-eye-open"></span>
          </div>
          <div id="resetNewPasswordErr" class="validate-error"></div>
        </div>
        <div class="form-group">
          <label for="resetConfirmPassword">Konfirmasi Password Baru</label>
          <div class="p-relative">
            <input type="password" class="form-control p-right-42px" id="resetConfirmPassword" placeholder="Ulangi password baru">
            <span id="toggleResetConfirmPassword" class="glyphicon icon-password glyphicon-eye-open"></span>
          </div>
          <div id="resetConfirmPasswordErr" class="validate-error"></div>
        </div>
        <div class="form-group">
          <div id="beErrReset" class="validate-error"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" id="btnSubmitResetPassword">Reset Password</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
