import axios from 'axios';
import axiosInterceptor from './axiosInterceptor';
import {environment} from './environment';

const wampServer = environment.URL + '/api/';
const axiosApi = axios.create({
    baseURL: wampServer,
});
// addToken=false → public/login endpoints, no auth header sent
axiosInterceptor.setupInterceptors(axiosApi, false, false);
export default axiosApi;
