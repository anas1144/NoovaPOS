import React, { useState } from "react";
import { Button } from "react-bootstrap-v5";
import { getFormattedMessage } from "../../shared/sharedMethod";
import ShopForm from "./ShopForm";

const AddShopButton = () => {
    const [show, setShow] = useState(false);
    const handleClose = () => setShow(!show);

    return (
        <div className="text-end w-sm-auto">
            <Button variant="primary mb-lg-0 mb-4" onClick={handleClose}>
                {getFormattedMessage("create.shop.title") || "Create Shop"}
            </Button>
            <ShopForm
                handleClose={handleClose}
                show={show}
                title={getFormattedMessage("create.shop.title") || "Create Shop"}
            />
        </div>
    );
};

export default AddShopButton;
