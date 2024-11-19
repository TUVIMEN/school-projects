<script setup lang="ts">
import NavBar from '../components/NavBar.vue'
import Seller from '../components/Seller.vue'
import Useller from '../components/Useller.vue'
import { store } from '../store'
import { getUser, getUserSellers, tryDeleteUser, tryUpdateUser } from '../api'
//import { ref } from 'vue';

</script>

<template>
    <nav-bar />
    <main class="mt-8 w-3/4 m-auto">
        <p v-if="errmsg.length > 0" class="text-[1.8em] text-red-500 text-center">
            {{errmsg}}
        </p>
        <div v-else-if="user !== null" class="space-y-4">
            <div class="m-auto flex flex-col space-y-6 mb-8">
                <div class="m-auto flex flex-col text-center space-y-3">
                    <img v-if="user.avatar" :src="'/.avatars/'+user.avatar" class="a_img m-auto text-center" />
                    <h2 class="text-blue-500 text-[2.2em]">{{user.name}}</h2>
                    <div class="flex space-x-2 justify-center">
                        <time class="p-2 bg-green-500 rounded-xl">{{user.created}}</time>
                        <time class="p-2 bg-green-800 rounded-xl">{{user.lastseen}}</time>
                        <div class="p-2 bg-orange-500 rounded-xl">{{user.email}}</div>
                    </div>
                </div>
                <div v-if="store.user !== null" class="flex flex-col space-y-2">
                    <div v-if="store.user.id === user.id || (store.user.perm as number) > (user.perm as number)" class="space-y-2 text-center">
                        <div class="bg-red-500 hover:bg-red-700 rounded-xl p-2 cursor-pointer" v-on:click="edituser=!edituser">Edytuj</div>
                        <div v-if="edituser === true">
                            <form v-on:submit.prevent="updateuser" class="flex flex-col space-y-4">
                                <input v-model="user.name" type="text" placeholder="nazwa" class="bg-gray-500" />
                                <input type="file" accept="image/png, image/jpeg" placeholder="avatar" class="bg-gray-500" v-on:change="changeavatar" ref="fileInput" />
                                <input v-model="user.email" type="text" placeholder="email" class="bg-gray-500" />
                                <input v-if="store.user && (store.user.perm as number) < (user.perm as number)" v-model="user.perm" type="number" min=1 :max="(user.perm as number)-1" placeholder="uprawnienia" class="bg-gray-500"/>
                                <button type="submit" class="p-2 bg-orange-500 hover:bg-orange-500">Zapisz</button>
                            </form>
                        </div>
                        <div class="bg-red-500 hover:bg-red-700 rounded-xl p-2 cursor-pointer" v-on:click="deluser">Usuń</div>
                    </div>
                    <useller label="Dodaj sprzedawcę" :userid="user.id" :userperm="user.perm" @changed="handlechanged" />
                </div>
                <div class="flex flex-col justify-center m-auto">
                    <RouterLink v-for="s in sellers" :to="'/seller/'+s.id" class="text-red-500 hover:text-red-800">{{s.created}} {{s.name}}</RouterLink>
                </div>
            </div>
        </div>
    </main>
</template>

<script lang="ts">
    export default {
        data() {
            return {
                user: null,
                userid: 0,
                sellers: [],
                edituser: false,
                avatar: "",
                errmsg: "",
            }
        },
        methods: {
            async deluser() {
                await tryDeleteUser(this.user.id);
                if (this.user.id === store.user.id) {
                    this.logout();
                }
                this.$router.push("/");
            },
            handlechanged() {
                getUserSellers(this.userid).then(r => this.sellers = r.data);
            },
            async loaduser() {
                let id = parseInt(this.$route.params.userid as string);
                if (isNaN(id) || id < 0) {
                    this.errmsg = "Incorrect user id";
                    return;
                }
                this.userid = id;

                if (store.user !== null && store.user.id === id) {
                    this.user = store.user;
                } else {
                    await getUser(id).then(r => this.user = r.data);
                }
            },
            async updateuser() {
                this.edituser = false;
                await tryUpdateUser(this.user.id,this.user.name,this.avatar,this.user.email,this.user.perm);
                await this.loaduser();
                if (store.user.id === this.user.id)
                    store.user = this.user;
            },
            changeavatar() {
                let f = new FileReader();
                f.readAsDataURL((this.$refs.fileInput as any).files[0]);
                f.onload = () => this.avatar = (f.result as string);
            },
            logout() {
                store.user = null;
                document.cookie = '';
            }
        },
        async mounted() {
            await this.loaduser();
            this.handlechanged();
        }
    }
</script>

<style scoped>
    img.a_img {
        width: 8em;
        max-height: 8em;
    }
</style>
