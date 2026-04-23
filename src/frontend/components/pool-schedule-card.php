<!-- I will take an instance of class schedule and use the attributes to fill this template component -->

<!DOCTYPE html>
<html>
<div>
    <hr>
    <h2>Schedules</h2>

    <?php if (count($filteredSchedules) === 0): ?>
        <p>"Sorry, I couldn't find any schedules that match what you're looking for"</p>
    <?php else: ?>

        <?php foreach ($filteredSchedules as $schedule): ?>
            <div>
                <h3><?php echo htmlspecialchars($schedule['subtype']); ?></h3>
                <p>
                    <strong>Effective dates:</strong>
                    <?php echo htmlspecialchars($schedule['effective_date']); ?>
                    to
                    <?php echo htmlspecialchars($schedule['end_date']); ?>
                </p>

                <table border="1" cellpadding="6" cellspacing="0">
                    <tr>
                        <th>Day</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Label</th>
                    </tr>

                    <?php foreach ($schedule['time_blocks'] as $block): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($block['day_of_week']); ?></td>
                            <td><?php echo htmlspecialchars($block['start_time']); ?></td>
                            <td><?php echo htmlspecialchars($block['end_time']); ?></td>
                            <td><?php echo htmlspecialchars($block['label']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <br>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>


</div>

</html>