import React from 'react';
import {Button} from 'react-bootstrap-v5';
import {Link} from 'react-router-dom';

const TableButton = ({ButtonValue, to}) => {
    // Strip any leading "#" left over from the old HashRouter so BrowserRouter
    // navigation works (e.g. "#/app/products/create" -> "/app/products/create").
    const path = typeof to === 'string' ? to.replace(/^#/, '') : to;
    return(
        <div className='text-end order-2 mb-2'>
            <Button as={Link} to={path} variant='primary'>{ButtonValue}</Button>
        </div>
    )
}

export default TableButton;
