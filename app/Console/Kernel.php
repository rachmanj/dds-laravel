
protected function schedule(Schedule $schedule)
{
    $schedule->command('sap:reconcile')->hourly();
}
