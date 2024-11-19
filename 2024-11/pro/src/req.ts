import axios from "axios";
import type { AxiosResponse } from "axios";

class BadResponseFormatError extends Error {
    constructor (public response: AxiosResponse) {
        super("Malformed response");
    }
}

/*axios.interceptors.response.use(
    (response: AxiosResponse) => {
        return response;
        if (response.headers["content-type"] !== "application/json") {
            throw new BadResponseFormatError(response);
        }

        try {
            response.data = JSON.parse(response.data);
            return response;
        } catch (err) {
            throw new BadResponseFormatError(response);
        }
    },
    (error) => {
        console.log("kkkkkkkkkkkkkkkkkkkkkkkkk");
        return Promise.reject(error);
    }
)*/

export function r_get(url: string): Promise<any> {
    return axios.get(url);
}

export function r_post(url: string, data: Object): Promise<any> {
    return axios.post(url,data);
}

export function r_delete(url: string): Promise<any> {
    return axios.delete(url);
}

export function r_patch(url: string, data: Object): Promise<any> {
    return axios.patch(url,data);
}
