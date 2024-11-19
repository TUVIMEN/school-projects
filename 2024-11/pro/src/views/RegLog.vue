<script lang="ts" setup>
import { RouterLink, RouterView } from 'vue-router';
import { store } from '../store'
import { getUserLog, tryLogin, tryRegister } from '../api';
</script>

<template>
  <div class="reglog text-center flex flex-col items-center justify-center h-screen text-white">
    <div class="button-tabs flex space-x-2">
        <RouterLink :highlighted="islogin" class="button-tab" to="/login">Login</RouterLink>
        <RouterLink :highlighted="!islogin" class="button-tab" to="/register">Register</RouterLink>
    </div>
    <form v-on:submit.prevent="login" v-if="islogin === true">
        <input required v-model="email" name="email" placeholder="email" type="email">
        <br>
        <input required v-model="password" name="password" placeholder="password" type="password">
        <br>
        <button type="submit">Login</button>
    </form>
    <form v-on:submit.prevent="register" v-else>
        <input required v-model="username" name="username" placeholder="username" type="text">
        <br>
        <input required v-model="email" name="email" placeholder="email" type="email">
        <br>
        <input required v-model="password" name="password" placeholder="password" type="password">
        <br>
        <input required v-model="repassword" name="repassword" placeholder="repassword" type="password">
        <br>
        <button type="submit">Register</button>
    </form>
    <div id="reglog_error" class="mt-4 text-red-500">{{errmsg}}</div>
  </div>
</template>

<script lang="ts">

export default {
  data() {
    return {
      islogin: false,
      username: null,
      email: null,
      password: null,
      repassword: null,
      errmsg: ""
    }
  },
  methods: {
    iflogin() {
        if (this.$route.name === "login") {
            this.islogin = true;
        } else {
            this.islogin = false;
        }
    },

    checkemail(): boolean {
        let email = this.email;
        if (email === null || !email.match(/^.+@[0-9a-zA-Z]+\.[a-zA-Z]+$/)) {
            this.errmsg = (email === null) ? "email field is empty" : "email is incorrect";
            return false;
        }
        return true;
    },

    checkpasswd(): boolean {
        let pw = this.password;
        if (pw === null || pw.length < 7) {
            this.errmsg = (pw === null) ? "password field is empty" : "password is too short";
            return false;
        }

        return true;
    },

    handleError(e) {
        if (e.status === 422) {
            this.errmsg = e.response.data.error;
        } else
            this.errmsg = e.message;
    },

    login() {
        if (!this.checkemail())
            return;
        if (!this.checkpasswd())
            return;

        tryLogin(this.email,this.password)
            .then(r => {
                this.$router.push("/");
                getUserLog(true);
            }).catch(e => this.handleError(e));
    },
    register() {
        if (!this.checkemail())
            return;
        if (!this.checkpasswd())
            return;

        if (this.repassword === null || this.password !== this.repassword) {
            this.errmsg = (this.repassword === null) ? "repassword field is empty" : "repassword and password are different";
            return;
        }

        if (this.username === null) {
            this.errmsg = "username field is empty";
            return;
        }

        tryRegister(this.username,this.email,this.password)
            .then(r => this.$router.push("/login"))
            .catch(e => this.handleError(e));
    }
  },
  mounted() {
    this.iflogin();
  }
}
</script>
