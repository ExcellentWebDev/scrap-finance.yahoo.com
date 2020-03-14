<template>
    <div>
        <go-top :size="40" :right="30" :bottom="50"></go-top>
        <b-container>
            <b-row>
                <b-col class="text-center" cols="12">
                    <h3 class="m-4 text-body "><ins>Welcome to "stock profile" site</ins></h3>
                </b-col>
                <b-col class="text-justify font-15 p-2" cols="12">
                    <span>This site is intended to provide neatly formatted detail of a stock's basic detail.</span><br/>
                    <span>There are several financial portals that have profiles of a company's stock 
                            but I have tried to gather some important detail and "have presented in one page" 
                            so we don't have to navigate to different pages !!!
                    </span><br/><br/>
                    <span>This details should assist a Day Trader and a swing Trader.</span><br/>
                    <span>You can enter multiple symbols at once to get their detail.</span><br/><br/>
                    <span>Good luck Trading !!!!!!!!!</span><br/><br/>
                    <span>Administrator</span><br/>
                    <span>"stock profile" </span><br/>
                    <span>+++++++++++++++++++++++++++++++++++++++++</span>
                </b-col>
            </b-row>
        </b-container>
        <div class="container">
            <div class="row mt-2">
                <div class="col-md-10 col-8">
                    <input type="text" class="form-control searchOption" placeholder="Input your Symbols (comma or space between symbols)"  v-model="symbol" @keyup.enter="search"/>
                </div>
                <div class="col-md-2 col-4 text-right">
                    <button class="btn btn-success" @click="search">Search</button>
                </div>
                <div class="col-12">
                    <hr size="3px" />
                </div>
            </div>
            <div class="mt-2">
                <div class="row" v-for="(item, index) in data" :key="index">
                    <div class="col-12">
                        <table class="table mt-2">
                            <tr :class="index % 2 == 0 ? 'bg-light' : 'bg-secondary-400'">
                                <td class="border-right-0 navyColor font-weight-bold font-18">Symbol</td>
                                <td class="text-right text-info border-left-0 font-18 navyColor font-weight-bold"><a href="#" @click="goSummaryPage(item.symbol)">{{item.symbol}}</a></td>
                                <td class="border-right-0 navyColor font-weight-bold font-18">Current Price</td>
                                <td class="text-right text-info border-left-0 navyColor font-weight-bold">
                                    <span class="font-16">{{item.current_price}} </span>
                                    <span class="font-13">{{item.increase}}</span>
                                </td>
                                <td class="border-right-0">Previous Close</td>
                                <td class="text-right border-left-0">{{item.previous_close}}</td>
                                <td class="border-right-0">Day's Range</td>
                                <td class="text-right border-left-0">{{item.day_range}}</td>
                            </tr>
                            <tr>
                                <td class="border-right-0">Volume</td>
                                <td class="text-right border-left-0">{{item.volume}}</td>
                                <td class="border-right-0">Avg.Volume</td>
                                <td class="text-right border-left-0">{{item.avg_volume}}</td>
                                <td class="border-right-0">Market Cap</td>
                                <td class="text-right border-left-0">{{item.market_cap}}</td>
                                <td class="border-right-0">Earnings Date</td>
                                <td class="text-right border-left-0">{{item.earning_date}}</td>
                            </tr>
                            <tr>
                                <td class="border-right-0">Shares Short</td>
                                <td class="text-right border-left-0">{{item.shares_short}}</td>
                                <td class="border-right-0">Short Ratio(DTC)</td>
                                <td class="text-right border-left-0">{{item.short_ratio_dtc}}</td>
                                <td class="border-right-0">Short % of Float</td>
                                <td class="text-right border-left-0">{{item.short_float}}</td>
                                <td class="border-right-0">Short % of Shares Outstanding</td>
                                <td class="text-right border-left-0">{{item.short_shares_outstanding}}</td>
                            </tr>
                            <tr>
                                <td class="border-right-0">Sector</td>
                                <td class="text-right border-left-0">{{item.sector}}</td>
                                <td class="border-right-0">Industry</td>
                                <td class="text-right border-left-0">{{item.industry}}</td>
                                <td class="border-right-0">Founded in year</td>
                                <td class="text-right border-left-0">{{item.was_founded}}</td>
                                <td class="border-right-0">Employees</td>
                                <td class="text-right border-left-0">{{item.full_time_employees}}</td>
                            </tr>
                            <tr v-if="!item.news.length">
                                <td colspan="8" class="text-center">Recent news not found</td>
                            </tr>
                            <tr v-for="(news, idx) in item.news" :key="idx">
                                <td colspan="8">
                                    <span class="navyColor font-weight-bold">RECENT NEWS :</span>
                                    <a href="#" @click="openNewsUrl(news.url)" >{{news.title}}</a>
                                </td>
                            </tr>
                        </table>
                        <div class="bg-primary" v-if="index != data.length - 1"></div>
                    </div>
                </div>
            </div>
            <div class="mt-2" v-if="showErrorMessage">
                <div class="row">
                    <div class="col-12 text-center">
                        <span class="text-danger h5">{{message}}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="vld-parent">
            <loading :active.sync="isLoading" :is-full-page="true" :height="80" :width="80" color="green" loader="bars"></loading>
        </div>
    </div>
</template>

<script>
import GoTop from '@inotom/vue-go-top';
import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/vue-loading.css';

export default {
    name: 'Home',
    components: { GoTop, Loading },
    data () {
        return {
            symbol: "",
            showErrorMessage: false,
            message: "",
            data: [],
            isLoading: false
        }
    },
    methods: {
        search: function() {
            this.isLoading = true;
            this.showErrorMessage = false;
            if (!this.symbol) {
                alert("Please input your Symbol."); return;
            }
            this.$store.dispatch('GET_STOCK', { symbol: this.symbol }).then(res => {
                this.data = res;
                if (!res.length) {
                    this.showErrorMessage = true;
                    this.message = "There is no seach result. Please confirm your Symbol.";
                }
                this.isLoading = false;
            }, err => {
                this.showErrorMessage = true;
                this.message = err.response.data;
                this.isLoading = false;
            })
        },
        openNewsUrl: function(url) {
            var url = "https://finance.yahoo.com" + url;
            window.open(url, "blank");
        },
        goSummaryPage: function(symbol) {
            var url = "https://finance.yahoo.com/quote/" + symbol + "?p=" + symbol;
            window.open(url, "blank");
        }
    },
    mounted() {
    }
}
</script>
<style scoped>
    .searchOption {
        background: #fff;
        height: 40px;
    }
    .searchOption:focus {
        border-color: #37c369;
    }
    td {
        border: 1px solid #dee2e6;
    }
    .bg-primary {
        height: 10px;
    }
    .border-left-right-0 {
        border-left: 0px !important;
        border-right: 0px !important;
    }
    .navyColor {
        color: navy;
    }
    @media (min-width: 1200px) {
        .container, .container-sm, .container-md, .container-lg, .container-xl {
            max-width: 1200px;
        }
    }
</style>