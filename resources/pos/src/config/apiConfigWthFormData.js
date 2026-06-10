import axios from 'axios';
import axiosInterceptor from './axiosInterceptor';
import {environment} from './environment';

const wampServer = environment.URL + '/api/';
const axiosApi = axios.create({
    baseURL: wampServer,
});
// addToken=true → attaches Bearer; isFormData=true → sets multipart Content-Type
axiosInterceptor.setupInterceptors(axiosApi, true, true);
export default axiosApi;
