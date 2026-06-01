import React from "react";
import { connect } from "react-redux";
import DeleteModel from "../../shared/action-buttons/DeleteModel";
import { deleteShop } from "../../store/action/shopAction";

const DeleteShop = (props) => {
    const { deleteShop, onDelete, deleteModel, onClickDeleteModel } = props;

    const deleteShopClick = () => {
        deleteShop(onDelete.id);
        onClickDeleteModel(false);
    };

    return (
        <div>
            {deleteModel && (
                <DeleteModel
                    onClickDeleteModel={onClickDeleteModel}
                    deleteModel={deleteModel}
                    deleteUserClick={deleteShopClick}
                    title="Delete Shop"
                    name="Shop"
                />
            )}
        </div>
    );
};

export default connect(null, { deleteShop })(DeleteShop);
