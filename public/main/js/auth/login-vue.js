const loginApp = new Vue({
  el: '#login-app',
	data: {
    form: {
      username: '',
      password: ''
    },
    message: '',
    usernameError: false,
    passwordError: false,
    passwordType: 'password',
    showPassword: false,
    showAlert: false,
    isSuccess: false,
    baseUrl: ''
  },
  mounted() { },
  computed: {
    disableSubmit: function() {
      return this.usernameError || this.passwordError;
    }
  },
	methods: {
    usernameKeyup: function(event) {
      const value = event.target.value;
      this.usernameError = !value ? true : false;
      this.passwordError = !this.form.password ? true : false;
    },
    passwordKeyup: function(event) {
      const value = event.target.value;
      this.passwordError = !value ? true : false;
      this.usernameError = !this.form.username ? true : false;
    },
    togglePassword: function() {
      this.showPassword = !this.showPassword;
      this.passwordType = !this.showPassword ? 'password' : 'text';
    },
		onSubmit: function() {
      const formData = {
        'username': this.form.username,
        'password': this.form.password
      }

      this.message = '';
      if (formData.username && formData.password) {
        $('#loading-screen').show();
        axios.post(this.$refs.baseUrl.value + '/api/masuk', formData, { headers: { "Content-Type": "application/json" } })
        .then(resp => {
          this.showAlert = true; this.isSuccess = true;
          this.message = 'Berhasil Masuk!';
          this.form.username = ''; this.form.password = '';
          const getDataLogin = resp.data;

          localStorage.setItem('vet-clinic', JSON.stringify({
            fullname: getDataLogin.fullname,
            username: getDataLogin.username,
            email: getDataLogin.email,
            role: getDataLogin.role,
            image_profile: getDataLogin.image_profile,
            token: getDataLogin.token,
            user_id: getDataLogin.user_id,
            branch_id: getDataLogin.branch_id,
            branch_name: getDataLogin.branch_name
          }));
        })
        .catch(err => {
          err.response.data.errors.forEach((element, idx) => {
            const msg = (idx !== 0 ) ? element + '<br>' : element;
            this.message += msg;
          });

          this.showAlert = true; this.isSuccess = false;
        })
        .finally(() => {
          $('#loading-screen').hide();
          setTimeout(() => {
            if (this.isSuccess) {
              this.showAlert = false;
              // Buka modal absensi sebelum redirect dashboard
              if (typeof window.bukaModalAbsensi === 'function') {
                const stored = JSON.parse(localStorage.getItem('vet-clinic'));
                window.bukaModalAbsensi(stored.token, this.$refs.baseUrl.value);
              } else {
                location.href = this.$refs.baseUrl.value + '/';
              }
            }
          }, 1000);
        });
      }
		}
	},
});
