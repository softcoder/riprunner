package com.vejvoda.android.riprunner;

public enum FireHallCalloutStatus {

    Paged(0),
    Notified(1),
    Responding(2),
    Cancelled(3),
    NotResponding(4),
    Standby(5),
    Responding_at_hall(6),
    Responding_to_scene(7),
    Responding_at_scene(8),
    Responding_return_hall(9),
    Complete(10);

    private int value;

    FireHallCalloutStatus(int value) {
        this.value = value;
    }
    public int valueOf() {
        return this.value;
    }
    public boolean isComplete() {
        return (this.value == FireHallCalloutStatus.Cancelled.valueOf() ||
                this.value == FireHallCalloutStatus.Complete.valueOf());

    }
    static public boolean isComplete(String status) {
        return (status != null &&
                (status.equals(String.valueOf(FireHallCalloutStatus.Cancelled.valueOf())) ||
                        (status.equals(String.valueOf(FireHallCalloutStatus.Complete.valueOf())))));
    }
}

