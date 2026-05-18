import React, {useState} from 'react';
import {connect} from 'react-redux';
import MasterLayout from '../MasterLayout';
import {fetchCurrencies} from '../../store/action/currencyAction';
import ReactDataTable from '../../shared/table/ReactDataTable';
import DeletCurrency from './DeletCurrency';
import CreateCurrency from './CreateCurrency';
import EditCurrency from './EditCurrency';
import TabTitle from '../../shared/tab-title/TabTitle';
import {getFormattedMessage, getPermission, placeholderText} from '../../shared/sharedMethod';
import ActionButton from '../../shared/action-buttons/ActionButton';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import { Permissions } from '../../constants';

const Currencies = (props) => {
    const {fetchCurrencies, currencies, totalRecord, isLoading, isCallFetchDataApi, allConfigData} = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [toggle, setToggle] = useState(false);
    const [currency, setCurrency] = useState();

    const handleClose = (item = null) => {
        setToggle(!toggle);
        setCurrency(item);
    };

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const onChange = (filter) => {
        fetchCurrencies(filter, true);
    };

    const itemsValue = currencies.length >= 0 && currencies.map(item => ({
        name: item.attributes.name,
        code: item.attributes.code,
        symbol: item.attributes.symbol,
        id: item.id
    }));

    const columns = [
        {
            name: getFormattedMessage('globally.input.name.label'),
            selector: row => row.name,
            sortable: true,
            sortField: 'name',
        },
        {
            name: getFormattedMessage('currency.modal.input.code.label'),
            selector: row => row.code,
            sortField: 'code',
            sortable: true,
            cell: row => {
                return <span className='badge bg-light-info'>
                            <span>{row.code}</span>
                        </span>
            }
        },
        {
            name: getFormattedMessage('currency.modal.input.symbol.label'),
            sortField: 'symbol',
            sortable: true,
            cell: row => {
                return <span className='badge bg-light-primary'>
                            <span>{row.symbol}</span>
                        </span>
            }
        },
        ...((
            getPermission(allConfigData?.permissions, Permissions.EDIT_CURRENCY) ||
            getPermission(allConfigData?.permissions, Permissions.DELETE_CURRENCY)
        ) ? [
            {
                name: getFormattedMessage('react-data-table.action.column.label'),
                right: true,
                ignoreRowClick: true,
                allowOverflow: true,
                button: true,
                cell: row => {
                    return <ActionButton
                        item={row}
                        goToEditProduct={handleClose}
                        isEditMode={getPermission(allConfigData?.permissions, Permissions.EDIT_CURRENCY)}
                        onClickDeleteModel={onClickDeleteModel}
                        isDeleteMode={getPermission(allConfigData?.permissions, Permissions.DELETE_CURRENCY)}
                    />
                }
            }] : []),
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('currencies.title')}/>
            <ReactDataTable
                columns={columns}
                items={itemsValue}
                onChange={onChange}
                isLoading={isLoading}
                totalRows={totalRecord}
                AddButton={getPermission(allConfigData?.permissions, Permissions.CREATE_CURRENCY) && <CreateCurrency />}
                isCallFetchDataApi={isCallFetchDataApi}
            />
            <EditCurrency handleClose={handleClose} show={toggle} currency={currency}/>
            <DeletCurrency onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel} onDelete={isDelete}/>
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    const {currencies, totalRecord, isLoading, isCallFetchDataApi, allConfigData} = state;
    return {currencies, totalRecord, isLoading, isCallFetchDataApi, allConfigData}
};

export default connect(mapStateToProps, {fetchCurrencies})(Currencies);

