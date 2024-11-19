import { store } from './store';
import { r_get, r_post, r_delete, r_patch } from './req'

export function getUser(userid: number): Promise<any> {
    return r_get("/api/userinfo" + ((userid > 0) ? ("/"+userid) : ""))
}

export function getUserSellers(userid: number): Promise<any> {
    return r_get("/api/usersellers/" + userid);
}

export function getUserLog(force=false) {
    if (store.user !== null && !force)
        return;

    store.user = null;
    r_get("/api/userinfo").then(r => store.user = r.data)
    .catch(e => {
        if (e.status === 401)
            return;
        return Promise.reject(e);
    });
}

export function getServices(force=false) {
    if (store.services !== null && !force)
        return;

    store.services = null;
    r_get("/api/services").then(r => store.services = r.data);
}

export function getCategories(force=false) {
    if (store.categories !== null && !force)
        return;

    store.categories = null;
    r_get("/api/categories").then(r => store.categories = r.data);
}

export function getMakes(force=false) {
    if (store.makes !== null && !force)
        return;

    store.makes = null;
    r_get("/api/makes").then(r => store.makes = r.data);
}

export function getCurrencies(force=false) {
    if (store.currencies !== null && !force)
        return;

    store.currencies = null;
    r_get("/api/currencies").then(r => store.currencies = r.data);
}

export function getParameters(force=false) {
    if (store.parameters !== null && !force)
        return;

    store.parameters = null;
    r_get("/api/parameters").then(r => store.parameters = r.data);
}

export function getDetails(force=false) {
    if (store.details !== null && !force)
        return;

    store.details = null;
    r_get("/api/details").then(r => store.details = r.data);
}

export function getEquipments(force=false) {
    if (store.equipments !== null && !force)
        return;

    store.equipments = null;
    r_get("/api/equipments").then(r => store.equipments = r.data);
}

export function getOffer(offerid: number): Promise<any> {
    return r_get("/api/offer/"+offerid);
}

export function getComments(sellerid: number): Promise<any> {
    return r_get("/api/comments/"+sellerid);
}


export function tryRegister(username: string, email: string, password: string): Promise<any> {
    return r_post("/api/register",{"name":username,"email":email,"password":password});
}

export function tryLogin(email: string, password: string): Promise<any> {
    return r_post("/api/login",{"email":email,"password":password});
}

export function tryComment(sellerid: number, value: string): Promise<any> {
    return r_post("/api/comment",{"sellerid":sellerid,"value":value});
}

export function getSearch(page: number, query: string|null, category: string|null, make: number|null): Promise<any> {
    return r_post("/api/search_category",{"page":page,"query":query,"category":category,"make":make});
}

export function getSellerSearch(sellerid: number, page: number, query: string|null, category: string|null, make: number|null): Promise<any> {
    return r_post("/api/seller",{"sellerid":sellerid,"page":page,"query":query,"category":category,"make":make});
}

export function tryAddSeller(seller: Object): Promise<any> {
    return r_post("/api/nseller",seller);
}

export function tryAddOffer(offer: Object): Promise<any> {
    return r_post("/api/noffer",offer);
}


export function tryDeleteComment(commentid: number): Promise<any> {
    return r_delete("/api/comment/"+commentid);
}

export function tryDeleteUser(commentid: number): Promise<any> {
    return r_delete("/api/member/"+commentid);
}

export function tryDeleteSeller(sellerid: number): Promise<any> {
    return r_delete("/api/seller/"+sellerid);
}

export function tryDeleteOffer(offerid: number): Promise<any> {
    return r_delete("/api/offer/"+offerid);
}


export function tryUpdateUser(userid: number, name: string, avatar: string, email: string, perm: string): Promise<any> {
    return r_patch("/api/member",{"memberid":userid,"name":name,"avatar":avatar,"perm":perm,"email":email});
}

export function tryUpdateSeller(seller: Object): Promise<any> {
    return r_patch("/api/seller",seller);
}

export function tryUpdateOffer(offer: Object): Promise<any> {
    return r_patch("/api/offer",offer);
}
