import React, { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import { fetchPlatformSubscriptions } from "../../store/action/platformAction";
import moment from "moment";

const statusBadge = (s) => {
    const map = {
        active: "bg-light-success",
        trialing: "bg-light-warning",
        past_due: "bg-light-danger",
        suspended: "bg-light-danger",
        canceled: "bg-light-secondary",
        expired: "bg-light-secondary",
    };
    return `badge text-capitalize ${map[s] || "bg-light-primary"}`;
};

const PlatformSubscriptions = () => {
    const dispatch = useDispatch();
    const subs = useSelector((s) => s.platform?.subscriptions || []);
    const isLoading = useSelector((s) => s.isLoading);

    useEffect(() => {
        dispatch(fetchPlatformSubscriptions());
    }, []);

    const columns = [
        { name: "Tenant", selector: (r) => r.tenant_name || r.tenant_id },
        { name: "Plan", selector: (r) => r.plan?.name || "-" },
        { name: "Price", selector: (r) => r.plan?.price || "-" },
        {
            name: "Billing",
            selector: (r) => r.plan?.billing_cycle,
            cell: (r) => (
                <span className="badge bg-light-primary text-capitalize">
                    {r.plan?.billing_cycle}
                </span>
            ),
        },
        {
            name: "Status",
            cell: (r) => <span className={statusBadge(r.status)}>{r.status}</span>,
        },
        {
            name: "Trial Ends",
            cell: (r) =>
                r.trial_ends_at ? moment(r.trial_ends_at).format("YYYY-MM-DD") : "-",
        },
        {
            name: "Renews",
            cell: (r) =>
                r.renews_at ? moment(r.renews_at).format("YYYY-MM-DD") : "-",
        },
        {
            name: "Ends",
            cell: (r) =>
                r.ends_at ? moment(r.ends_at).format("YYYY-MM-DD") : "-",
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Subscriptions" />
            <ReactDataTable
                columns={columns}
                items={subs}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                onChange={() => dispatch(fetchPlatformSubscriptions())}
            />
        </MasterLayout>
    );
};

export default PlatformSubscriptions;
