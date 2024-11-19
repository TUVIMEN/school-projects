<script setup lang="ts">
import NavBar from '../components/NavBar.vue'
import Seller from '../components/Seller.vue'
import Uoffer from '../components/Uoffer.vue'
import { store } from '../store'
import { getOffer, tryDeleteOffer } from '../api'

</script>

<template>
    <nav-bar />
    <br>
    <div class="mt-4" v-if="errmsg.length > 0">
        <p class="text-red-500 text-center text-[1.8em]">{{errmsg}}</p>
    </div>
    <div v-else-if="offer !== null" class="w-3/4 m-auto mt-4 space-y-4">
        <div class="flex space-x-3">
            <div v-if="offer.photos.length > 0" class="w-full flex flex-col space-y-4">
                <div class="flex relative justify-between">
                    <div class="rounded-xl p-4 text-[2.2em] select-none cursor-pointer hover:text-blue-600 justify-center my-auto" v-on:click="gallery(-1)">&lt;</div>
                    <div class="bg-red-400 w-5/7">
                        <img :src="showphoto(offer.photos[currentimage].src)" />
                    </div>
                    <div class="rounded-xl p-4 text-[2.2em] select-none cursor-pointer hover:text-blue-600 justify-center my-auto" v-on:click="gallery(1)">&gt;</div>
                    <div class="absolute rounded bg-gray-800 opacity-90 bottom-2 right-2 p-2 text-xl">{{currentimage+1}}/{{offer.photos.length}}</div>
                </div>
                <div class="overflow-x-auto space-x-2 snap-x snap-mandatory flex">
                    <div v-for="(p, index) in offer.photos" class="p-2 w-1/3 snap-center cursor-pointer bg-gray-600 hover:bg-gray-800 galleryimg" :highlighted="index === currentimage" v-on:click="gallery(index,true)">
                        <img :src="showphoto(p.src)" class="justify-center" />
                    </div>
                </div>
            </div>
            <div class="w-1/3">
                <h2 class="text-blue-500">{{offer.title}}</h2>
                <RouterLink :to="'/seller/'+offer.sellerid" class="text-purple-500 hover:bg-red-800">{{offer.seller.name}}</RouterLink>
                <p class="text-red-500">{{offer.price}} {{offer.currecy_name}}</p>
                <time class="text-green-500">{{offer.created}}</time>
                <p class="text-yellow-500">{{offer.category_name}}</p>
                <div v-if="offer.description.length > 0">
                    <p class="mt-4">Opis:</p>
                    <div v-html="offer.description"></div>
                </div>
            </div>
        </div>

        <div v-if="store.user !== null && (store.user.id === offer.seller.userid || offer.seller.userperm === null || (offer.seller.userperm as number) < (store.user.perm as number))" class="space-y-3">
            <uoffer label="edytuj" :data="offer" :userid="offer.seller.userid" :userperm="offer.seller.userperm" :sellerid="offer.seller.id" @changed="handlechanged" />
            <div class="my-auto p-1 hover:bg-blue-800 bg-red-700 cursor-pointer rounded-xl text-xl" v-on:click="deloffer">Usuń</div>
        </div>

        <div v-if="offer.details.length > 0">
            <h3 class="text-[2.5em]">Szczegóły</h3>
            <div class="space-y-4">
                <div v-for="d in offer.details" class="flex" style="border-bottom: 1px solid red;">
                    <div class="w-1/2 text-gray-300">{{d.name}}</div>
                    <div class="w-1/2">{{d.value}}</div>
                </div>
            </div>
        </div>

        <div v-if="offer.parameters.length > 0">
            <h3 class="text-[2.5em]">Specyfikacja</h3>
            <div class="space-y-4">
                <div v-for="p in offer.parameters" class="flex" style="border-bottom: 1px solid red;">
                    <div class="w-1/2 text-gray-300">{{p.name}}</div>
                    <div class="w-1/2">{{p.value}}</div>
                </div>
            </div>
        </div>

        <div v-if="offer.equipments.size > 0" class="space-y-3">
            <h3 class="text-[2.5em]">Wyposażenie</h3>
            <div v-for="[key,e] in offer.equipments" class="space-y-4">
                <h3 class="text-[2em]">{{key}}</h3>
                <div v-for="n in e" style="border-bottom: 1px solid red;">
                    {{n.name}}
                </div>
            </div>
        </div>

        <seller :data="offer.seller" />
        <p class="mb-10"></p>
    </div>

</template>

<script lang="ts">

function offerFinalize(o) {
    let eq = new Map();

    for (let e of o.equipments) {
        if (!eq.get(e.category_name))
            eq.set(e.category_name,[]);
        eq.set(e.category_name,eq.get(e.category_name).concat({"id":e.id,"name":e.name}));
    }

    o.equipments = eq;
    return o;
}

export default {
    data() {
        return {
            offer: null,
            offerid: 0,
            errmsg: "",
            currentimage: 0,
        };
    },
    methods: {
        showphoto(i: string|null): string {
            if (i === null)
                return "";
            if (i.slice(0,8) === "https://")
                return i;
            return '/.offer_photos/' + i;
        },
        handlechanged() {
            getOffer(this.offerid).then(r => this.offer = offerFinalize(r.data));
        },
        async deloffer() {
            await tryDeleteOffer(this.offerid);
            this.$router.push('/');
        },
        gallery(num: number, set=false) {
            if (!set) {
                this.currentimage += num;
                if (this.currentimage < 0) {
                    this.currentimage += this.offer.photos.length;
                } else if (this.currentimage >= this.offer.photos.length)
                    this.currentimage -= this.offer.photos.length;
            } else {
                this.currentimage = num;
            }
        }

    },
    mounted() {
        let p = parseInt(this.$route.params.offerid as string);
        if (isNaN(p)) {
            this.errmsg = "Invalid offer id";
            return;
        }
        this.offerid = p;

        this.handlechanged();
    }
}

</script>

<style>
    .galleryimg[highlighted="true"] {
        background-color: blue;
    }
</style>
