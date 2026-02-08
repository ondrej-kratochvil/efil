// Configuration
import { getBasePath } from './utils.js';

export const BASE_PATH = getBasePath();
export const API_BASE = BASE_PATH ? BASE_PATH + '/api' : '/api';
