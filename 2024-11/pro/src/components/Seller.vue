<script lang="ts" setup>
import { RouterLink, RouterView } from 'vue-router';
import { store } from '../store'
import { getComments, tryComment, tryDeleteComment, tryDeleteSeller } from '../api'
import { weekday } from '../o'
import Mlocation from "./Mlocation.vue"
import Useller from "./Useller.vue"
import Uoffer from "./Uoffer.vue"
</script>

<template>
  <div class="text-center flex justify-center items-center flex-col m-auto space-y-5 w-1/2">
      <img v-if="data.logo.length > 0" :src="logopath(data.logo[0].src)" :alt="data.logo[0].alt"/>
      <RouterLink :to="'/seller/'+data.id" class="hover:bg-blue-500 p-2 rounded-xl"><h2 class="text-[1.7em]">{{data.name}}</h2></RouterLink>
      <p v-if="data.phonenumber.length > 0" class="text-blue-500">Tel: {{data.phonenumber}}</p>
      <a v-if="data.website.length > 0" :href="data.website" class="text-orange-500 hover:text-orange-800">{{data.website}}</a>
      <p v-if="data.created" class="text-green-500">Sprzedaje od {{data.created}}</p>
      <div class="flex flex-wrap justify-center space-x-2 space-y-2">
        <div v-for="b in data.badges" class="rounded-xl text-[0.8em] bg-blue-500 p-2">
            {{b.name}}
        </div>
      </div>
      <div v-if="data.services.length > 0" class="space-y-3">
        <h3>Usługi</h3>
        <div class="flex flex-row space-x-2 flex-wrap justify-center space-y-2">
            <div v-for="s in data.services" class="bg-green-500 rounded-xl flex flex-col p-2 w-30 h-30 text-[0.9em]">
                <img :src="s.src" class="w-[4em] m-auto" />
                <span>{{s.name}}</span>
            </div>
        </div>
      </div>
      <div v-if="data.workinghours.length > 0" class="space-y-3">
        <h3>Godziny pracy</h3>
        <table>
            <tr v-for="w in data.workinghours" class="flex space-x-4">
                <td class="w-2/4">{{weekday(w.day)}}: </td>
                <td class="w-3/4" v-if="w.openhour === '0' && w.openminute === '0' && w.closehour === '0' && w.closeminute === '0'">
                    <span>Zamknięte</span>
                </td>
                <td v-else class="space-x-4 w-3/4">
                    <span>{{dd(w.openhour)}}:{{dd(w.openminute)}}</span>
                    <span>-&gt;</span>
                    <span>{{dd(w.closehour)}}:{{dd(w.closeminute)}}</span>
                </td>
            </tr>
        </table>
      </div>
      <mlocation :data="data.location" />
      <div class="bg-red-500 hover:bg-red-800 rounded-xl p-2 cursor-pointer" v-on:click="togglecomments">Komentarze</div>
      <div v-if="showcomments" class="space-y-2">
        <div v-if="store.user !== null">
            <form v-on:submit.prevent="sendcomment" class="flex flex-col space-y-2">
                <textarea required v-model="commenttext" placeholder="Skomentuj" class="bg-gray-500 w-full h-20"></textarea>
                <button type="submit" class="bg-blue-700 hover:bg-blue-900 cursor-pointer w-1/3 rounded-xl p-2 m-auto">Wyślij</button>
            </form>
        </div>
        <div>
            <div v-if="comments.length === 0">
                Brak komentarzy
            </div>
            <div v-else class="space-y-2">
                <div v-for="c in comments" class="flex space-x-2 border-b-2 border-solid border-red-900">
                    <div class="flex flex-col space-y-2 w-15 border-r-2 border-solid border-blue-900">
                        <RouterLink :to="'/user/'+c.id">
                            <img v-if="c.avatar !== null && c.avatar.length > 0" :src="'/.avatars/'+c.avatar" class="c_img" />
                            <p class="text-purple-500">{{c.name}}</p>
                        </RouterLink>
                        <time class="text-green-500">{{c.created}}</time>
                    </div>
                    <div class="" style="width: 50rem;">
                        {{c.value}}
                    </div>
                    <div v-if="store.user !== null && (store.user.id === c.userid || (store.user.perm as number) > (c.perm as number))" class="my-auto p-1 hover:bg-blue-800 cursor-pointer rounded-xl text-xl" v-on:click="delcomment(c.id as number)">X</div>
                </div>
            </div>
        </div>
      </div>
      <div v-if="full !== undefined && store.user !== null && (store.user.id === data.userid || data.userperm === null || (data.userperm as number) < (store.user.perm as number))" class="space-y-3">
          <useller label="edytuj" :data="data" :userid="data.userid" :userperm="data.userperm" @changed="handlechanged" />
          <div class="my-auto p-1 hover:bg-blue-800 bg-red-700 cursor-pointer rounded-xl text-xl" v-on:click="delseller">Usuń</div>
          <uoffer label="Dodaj" :userid="data.userid" :userperm="data.userperm" :sellerid="data.id" @changed="handlechanged" />
      </div>
  </div>
</template>

<script lang="ts">
export default {
    data() {
        return {
            showcomments: false,
            triedcomments: false,
            comments: [],
            commenttext: "",
        }
    },
    props: [
        'data',
        'full'
    ],
    methods: {
        delseller() {
            tryDeleteSeller(this.data.id);
            this.$router.push('/');
        },
        handlechanged() {
            this.$emit('changed');
        },
        logopath(i: string|null): string {
            if (i === null)
                return "";
            if (i.slice(0,8) === "https://")
                return i;
            return '/.logos/' + i;
        },
        gco() {
            getComments(this.data.id).then(r => this.comments = r.data);
        },
        async delcomment(commentid: number) {
            await tryDeleteComment(commentid);
            this.gco();
        },
        togglecomments() {
            this.showcomments = !this.showcomments;
            if (!this.showcomments || this.triedcomments)
                return;
            this.triedcomments = true;
            this.gco();
        },
        async sendcomment() {
            if (store.user === null || this.commenttext.trim().length === 0)
                return;
            let value = this.commenttext;
            this.commenttext = "";
            await tryComment(this.data.id,value);
            this.gco();
        },
        dd(n) {
            let n2 = parseInt(n);
            if (isNaN(n2))
                return;
            if (n2 < 10)
                return "0" + n2;
            return n2;
        }
    }
}
</script>

<style scoped>
    img.c_img {
        width: 6em;
        margin: auto;
        text-align: center;
    }
</style>
