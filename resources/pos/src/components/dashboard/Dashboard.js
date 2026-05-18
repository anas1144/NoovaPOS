import React, { useState } from 'react';
import DateRangePicker from '../../shared/datepicker/DateRangePicker';
import { useSelector } from 'react-redux';
import MasterLayout from '../MasterLayout';
import TabTitle from '../../shared/tab-title/TabTitle';
import TodaySalePurchaseCount from './TodaySalePurchaseCount';
import RecentSale from './RecentSale';
import TopSellingProduct from './TopSellingProduct';
import { getPermission, placeholderText } from '../../shared/sharedMethod';
import ThisWeekSalePurchaseChart from "./ThisWeekSalePurchaseChart";
import StockAlert from "./StockAlert";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import { Permissions } from '../../constants';

const Dashboard = () => {
    const { frontSetting, allConfigData } = useSelector( state => state );
    const [selectDate, setSelectDate] = useState();

    const onDateSelector = (date) => {
        setSelectDate(date.params);
    }

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText( 'dashboard.title' )} />
            <div className="row align-items-center mb-1">
                <div className="col-12 col-md-6" />
                <div className="col-12 col-md-6 d-flex justify-content-end">
                    <DateRangePicker onDateSelector={onDateSelector} selectDate={selectDate} isProfitReport={true} />
                </div>
            </div>

            <TodaySalePurchaseCount selectDate={selectDate} frontSetting={frontSetting} />
            <ThisWeekSalePurchaseChart selectDate={selectDate} frontSetting={frontSetting} />
            <TopSellingProduct selectDate={selectDate} frontSetting={frontSetting} />
            {getPermission(allConfigData?.permissions, Permissions.MANAGE_CUSTOMERS) && <RecentSale frontSetting={frontSetting} />}
            <StockAlert frontSetting={frontSetting} />
        </MasterLayout>
    )
}

export default Dashboard;
